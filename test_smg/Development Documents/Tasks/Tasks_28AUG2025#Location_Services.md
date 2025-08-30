# 2025-08-28 Location Services – New Logic Migration Plan and Analysis

## context
- Date: 2025-08-28
- Scope: Fully migrate to the new, DB-first connection system (source_position/target_position, edge_type, is_enabled, action_label, keyboard_shortcut) and remove legacy JSON- and connection_type-dependent logic.
- Goal: Ensure all movement/connection behaviors (town/road/dungeon, dice, branches, direct move, keyboard) are driven solely by route_connections + routes and the new APIs, with consistent UI.

## summary of findings (root cause of current UI inconsistency)
- DB truth is correct: road_1 at position 0 connects to town_prima.
  - Verified rows (SQLite route_connections):
    - source=road_1 → target=town_prima, source_position=0, edge_type=normal, is_enabled=1
    - source=town_prima → target=road_1, target_position=0, edge_type=normal, is_enabled=1
- Legacy logic in `LocationService::getNextLocationFromRoad` only checks connection_type ('start'/'end') and not new position fields; current data has connection_type NULL, so it returns null.
- Blade templates for road state fall back to a hard-coded label ('セカンダ町') when nextLocation is null (e.g., `game-states/road-sidebar.blade.php`).
- Therefore, at position=0 on road_1, the UI shows the fallback 'セカンダ町' instead of 'プリマ' because `nextLocation` is null even though DB data is correct.

## affected components
- Backend:
  - `app/Domain/Location/LocationService.php`
    - getNextLocationFromRoad (legacy depends on connection_type)
    - calculateStartPosition (JSON- and direction-based)
    - getTownConnectionsFromConfig / getTownConnections (mixes legacy notions)
    - loadConfigData (initializes empty but preserves JSON fallbacks)
    - moveDirectly / getNextLocation / canMoveDirectly (uses legacy nextLocation path)
    - hasBranchAt / getBranchOptions (reads via connection_type=branch now, but naming/comments mention config)
    - getAvailableConnections / shouldShowConnectionAtPosition / getAvailableConnectionsWithData (new logic; keep)
  - `app/Application/Services/GameDisplayService.php` (uses nextLocation)
  - `app/Application/Services/GameStateManager.php` (many call sites rely on nextLocation and legacy calculations)
  - `app/Http/Controllers/GameController.php` (prepareUnifiedLayoutData wires both nextLocation and availableConnections)
  - `app/Models/RouteConnection.php` (scopes reference connection_type; new logic uses source_position/target_position)
- Frontend (Blade):
  - `resources/views/game-states/road-sidebar.blade.php` (fallback 'セカンダ町', uses nextLocation; ignores availableConnections)
  - Other road/town partials using `nextLocation` or hard-coded fallbacks
  - `resources/views/game/partials/next_location_button.blade.php` (has new logic path but also legacy fallback)
- Data/Seeders:
  - Seeders/migrations that still set or rely on connection_type; new model prefers position fields.

## migration strategy
- Principle: Drive all connection/transition calculations from route_connections (source_position/target_position + is_enabled + edge_type + keyboard_shortcut) and routes.category. Avoid JSON/config fallbacks.
- UI principle: Show actions based on `getAvailableConnectionsWithData(player)`; avoid computing a singular nextLocation for roads. For towns with multiple exits, also use the same unified connection view.

## tasks – backend
1) Replace legacy nextLocation resolution
   - Refactor `LocationService::getNextLocationFromRoad` to support new schema:
     - When position=0 → pick enabled connection(s) with source_position=0.
     - When position=100 → pick enabled connection(s) with source_position=100.
     - Middle positions (e.g., 50) → source_position exact.
     - If multiple matches exist, either return the first (for legacy compat) or deprecate method in favor of availableConnections.
   - Mark `getNextLocationFromRoad` as deprecated in PHPDoc and redirect all callers to `getAvailableConnectionsWithData` where feasible.

2) Deprecate/Remove JSON fallbacks
   - Remove calls to `loadConfigData()` from runtime paths and delete JSON resolution branches.
   - Remove or archive `getTownConnectionsFromConfig` and other config-based getters.
   - Ensure all names come from `routes` table (keep getTownNameFromConfig/getRoadNameFromConfig as DB lookups).

3) Unify town connection logic
   - Implement town connections as route_connections where `source_position` IS NULL (town node) or a dedicated edge_type (e.g., 'enter/exit').
   - Update `getTownConnections` to query route_connections by source_location_id and source_position IS NULL (enabled only) and produce direction labels via edge/label fields.
   - Remove direction inference from JSON/direction; use `direction` (if kept) or `action_label` for UI text, and `keyboard_shortcut` for keys.

4) Movement entry position
   - Replace `calculateStartPosition` JSON/direction heuristics with:
     - When moving via a chosen connection, use that connection's `target_position` if not null, else default 0 for towns.
   - Update `LocationService::moveToConnectionTarget` as the single entry point for moves; ensure all move paths (direct move, keyboard move, commit from dice/boundaries) call this function with a chosen connection.

5) Branches and special actions
   - Ensure branch detection uses connections where source_position is the branch position and edge_type='branch' (or a dedicated flag), not JSON.
   - Keep `shouldShowConnectionAtPosition` rules; extend tests for 0/50/100 and middle values.

6) Clean up RouteConnection model
   - Mark connection_type/position/direction as legacy; stop using scopes that depend on them in production code.
   - Introduce helper scopes: `bySourcePosition()`, `byEnabled()`, `atBoundary(0|50|100)`, etc., built on new fields.
   - Optionally add a DB migration later to drop legacy columns once all usage is removed.

7) Game state services
   - GameDisplayService: stop computing `nextLocation`; use availableConnections for both town and road contexts. For town, it lists exits; for roads, it lists reachable transitions at current position/boundaries.
   - GameStateManager: replace usages of `getNextLocation`/`moveToNextLocation` with a flow that picks a specific connection id and calls `moveToConnectionTarget`.
   - Keyboard and auto-move should select a concrete connection by `keyboard_shortcut` or by boundary rule.

## tasks – frontend (Blade/UI)
8) Remove hard-coded fallbacks
   - Replace all occurrences like `{{ $nextLocation->name ?? 'セカンダ町' }}` with new-connections UI fed by `$availableConnections`.
   - Where a summary label is desired at boundaries, compute from `$availableConnections` (if exactly one, show its target name; if multiple, show a selection panel).

9) Unify partials
   - `resources/views/game-states/road-sidebar.blade.php`: show `availableConnections` buttons (ActionLabel + keyboard hints) instead of `nextLocation` area.
   - `resources/views/game/partials/next_location_button.blade.php`: remove legacy block; use only connection-based buttons.
   - Similar cleanup in road-main, road-right/left backups (if still used), and town-sidebars to consistently use `$availableConnections` or `$townConnections` (re-implemented via DB).

10) Keyboard UX
   - Ensure `moveByKeyboard` uses `keyboard_shortcut` and `shouldShowConnectionAtPosition` (already implemented). Expose hints in UI via `getKeyboardDisplay()`.

## tasks – data & admin
11) Data normalization
   - Ensure all start/end/bidirectional/branch connections are represented with source_position/target_position and edge_type rather than connection_type.
   - For towns, create outgoing connections with source_position=NULL and appropriate `action_label`/`keyboard_shortcut`.

12) Admin screens
   - Confirm Admin pages create/edit route_connections with new fields; hide or label legacy fields as deprecated.
   - Add validations so that connections at the same `source_location_id` + `source_position` + `keyboard_shortcut` do not conflict.

13) Migrations (later phase)
   - After code is free of legacy, create migration to drop `connection_type`, `position`, `direction` columns from route_connections.

## code references (for implementers)
- New logic (keep):
  - `LocationService::getAvailableConnections`, `shouldShowConnectionAtPosition`, `getAvailableConnectionsWithData`, `moveToConnectionTarget`.
- Legacy (to migrate/retire):
  - `getNextLocationFromRoad`, `getNextLocationFromTown`, `getTownConnectionsFromConfig`, `calculateStartPosition` (JSON/direction), JSON fallbacks in `loadConfigData` and elsewhere.
- UI fallbacks to remove:
  - `resources/views/game-states/road-sidebar.blade.php` and similar: hard-coded 'セカンダ町'.

## acceptance criteria
- At road position 0/100/50, the UI presents available connections sourced from DB; no hard-coded names appear.
- Town exits appear from DB connections (source_position=NULL); multiple exits render as multiple buttons with action labels.
- All moves (button and keyboard) call `moveToConnectionTarget` with a specific connection id; player location/position updates from that connection’s target info.
- No code path reads from JSON config; unit/integration tests pass.
- `connection_type` is not used by runtime logic; optional: later DB migration drops it.

## risks & mitigations
- Risk: Hidden dependencies on nextLocation in other partials.
  - Mitigation: Grep for `nextLocation` usages and replace; add tests for road/town screens.
- Risk: Multiple available connections at boundaries break assumptions.
  - Mitigation: UI shows list; if exactly one, auto-highlight and provide a single button.
- Risk: Admin data inconsistency.
  - Mitigation: Validation on create/edit, DB constraints if feasible, seeders updated.

## rollout plan
- Phase 1: Implement backend changes with feature flag to allow both paths; flip UI to connection-based rendering; remove fallbacks.
- Phase 2: Remove legacy methods and JSON code; adjust tests; enable keyboard flows.
- Phase 3: Data cleanup (ensure all connections have positions), then drop legacy columns.

## test plan (minimum)
- Unit: `shouldShowConnectionAtPosition` for positions {null,0,50,100,middle} and edge cases.
- Integration: From road_1 at pos 0, availableConnections contains the town_prima edge; moveToConnectionTarget updates location to town.
- UI smoke: Road sidebar shows correct connection label instead of fallback.

