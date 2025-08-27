CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "last_active_at" datetime,
  "last_device_type" varchar,
  "last_ip_address" varchar,
  "session_data" text,
  "is_admin" tinyint(1) not null default '0',
  "admin_activated_at" datetime,
  "admin_last_login_at" datetime,
  "admin_role_id" integer,
  "admin_permissions" text,
  "admin_level" varchar not null default 'basic',
  "admin_requires_2fa" tinyint(1) not null default '0',
  "admin_ip_whitelist" text,
  "admin_permissions_updated_at" datetime,
  "admin_created_by" integer,
  "admin_notes" text
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "facility_items"(
  "id" integer primary key autoincrement not null,
  "facility_id" integer not null,
  "item_id" integer not null,
  "price" integer not null,
  "stock" integer not null default '-1',
  "is_available" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("facility_id") references "shops"("id") on delete cascade,
  foreign key("item_id") references "items"("id") on delete cascade
);
CREATE UNIQUE INDEX "shop_items_shop_id_item_id_unique" on "facility_items"(
  "facility_id",
  "item_id"
);
CREATE TABLE IF NOT EXISTS "items"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "description" text,
  "category" varchar not null,
  "stack_limit" integer,
  "max_durability" integer,
  "effects" text,
  "value" integer not null default '0',
  "sell_price" integer,
  "battle_skill_id" varchar,
  "weapon_type" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "characters"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null default('冒険者'),
  "attack" integer not null default('10'),
  "defense" integer not null default('8'),
  "agility" integer not null default('12'),
  "evasion" integer not null default('15'),
  "hp" integer not null default('100'),
  "max_hp" integer not null default('100'),
  "sp" integer not null default('30'),
  "max_sp" integer not null default('30'),
  "mp" integer not null default('20'),
  "max_mp" integer not null default('20'),
  "magic_attack" integer not null default('8'),
  "accuracy" integer not null default('85'),
  "gold" integer not null default('1000'),
  "created_at" datetime,
  "updated_at" datetime,
  "user_id" integer not null,
  "location_type" varchar not null default('town'),
  "location_id" varchar not null default('town_a'),
  "game_position" integer not null default('0'),
  "last_visited_town" varchar not null default('town_a'),
  "experience_to_next" integer not null default('100'),
  "level" integer not null default '1',
  "experience" integer not null default '0',
  "base_attack" integer not null default '10',
  "base_defense" integer not null default '8',
  "base_agility" integer not null default '12',
  "base_evasion" integer not null default '15',
  "base_max_hp" integer not null default '100',
  "base_max_sp" integer not null default '30',
  "base_max_mp" integer not null default '20',
  "base_magic_attack" integer not null default '8',
  "base_accuracy" integer not null default '85',
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "characters_user_id_unique" on "characters"("user_id");
CREATE TABLE IF NOT EXISTS "battle_logs"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "monster_name" varchar not null,
  "location" varchar not null,
  "result" varchar check("result" in('victory', 'defeat', 'escaped')) not null,
  "experience_gained" integer not null default '0',
  "gold_lost" integer not null default '0',
  "turns" integer not null default '1',
  "battle_data" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "battle_logs_user_id_created_at_index" on "battle_logs"(
  "user_id",
  "created_at"
);
CREATE INDEX "battle_logs_result_index" on "battle_logs"("result");
CREATE TABLE IF NOT EXISTS "active_battles"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "battle_id" varchar not null,
  "character_data" text not null,
  "monster_data" text not null,
  "battle_log" text not null default '[]', "turn" integer not null default '1',
  "location" varchar,
  "status" varchar not null default 'active',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "active_battles_user_id_index" on "active_battles"("user_id");
CREATE UNIQUE INDEX "active_battles_battle_id_unique" on "active_battles"(
  "battle_id"
);
CREATE TABLE IF NOT EXISTS "skills"(
  "id" integer primary key autoincrement not null,
  "player_id" integer not null,
  "skill_type" varchar not null default('combat'),
  "skill_name" varchar not null,
  "level" integer not null default('1'),
  "experience" integer not null default('0'),
  "effects" text,
  "sp_cost" integer not null default('10'),
  "duration" integer not null default('5'),
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("player_id") references "players"("id") on delete cascade
);
CREATE INDEX "skills_skill_type_index" on "skills"("skill_type");
CREATE INDEX "skills_player_id_skill_name_index" on "skills"(
  "player_id",
  "skill_name"
);
CREATE TABLE IF NOT EXISTS "inventories"(
  "id" integer primary key autoincrement not null,
  "player_id" integer not null,
  "slot_data" text not null default('[]'), "max_slots" integer not null default('10'),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("player_id") references "players"("id") on delete cascade
);
CREATE INDEX "inventories_character_id_index" on "inventories"("player_id");
CREATE UNIQUE INDEX "inventories_player_id_unique" on "inventories"(
  "player_id"
);
CREATE TABLE IF NOT EXISTS "active_effects"(
  "id" integer primary key autoincrement not null,
  "player_id" integer not null,
  "effect_name" varchar not null,
  "effects" text not null,
  "remaining_duration" integer not null,
  "source_type" varchar not null default('skill'),
  "source_id" integer,
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("player_id") references "players"("id") on delete cascade
);
CREATE INDEX "active_effects_character_id_is_active_index" on "active_effects"(
  "player_id",
  "is_active"
);
CREATE INDEX "active_effects_effect_name_index" on "active_effects"(
  "effect_name"
);
CREATE TABLE IF NOT EXISTS "equipment"(
  "id" integer primary key autoincrement not null,
  "player_id" integer not null,
  "weapon_id" integer,
  "body_armor_id" integer,
  "shield_id" integer,
  "helmet_id" integer,
  "boots_id" integer,
  "accessory_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("weapon_id") references items("id") on delete set null on update no action,
  foreign key("body_armor_id") references items("id") on delete set null on update no action,
  foreign key("shield_id") references items("id") on delete set null on update no action,
  foreign key("helmet_id") references items("id") on delete set null on update no action,
  foreign key("boots_id") references items("id") on delete set null on update no action,
  foreign key("accessory_id") references items("id") on delete set null on update no action,
  foreign key("player_id") references "players"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "players"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "name" varchar not null default('冒険者'),
  "level" integer not null default('1'),
  "experience" integer not null default('0'),
  "experience_to_next" integer not null default('100'),
  "attack" integer not null default('10'),
  "defense" integer not null default('8'),
  "agility" integer not null default('12'),
  "evasion" integer not null default('15'),
  "magic_attack" integer not null default('8'),
  "accuracy" integer not null default('85'),
  "hp" integer not null default('100'),
  "max_hp" integer not null default('100'),
  "mp" integer not null default('20'),
  "max_mp" integer not null default('20'),
  "sp" integer not null default('30'),
  "max_sp" integer not null default('30'),
  "base_attack" integer not null default('10'),
  "base_defense" integer not null default('8'),
  "base_agility" integer not null default('12'),
  "base_evasion" integer not null default('15'),
  "base_max_hp" integer not null default('100'),
  "base_max_sp" integer not null default('30'),
  "base_max_mp" integer not null default('20'),
  "base_magic_attack" integer not null default('8'),
  "base_accuracy" integer not null default('85'),
  "location_type" varchar not null default('town'),
  "location_id" varchar not null default('town_a'),
  "game_position" integer not null default('0'),
  "last_visited_town" varchar not null default('town_a'),
  "gold" integer not null default '1000',
  "location_data" text,
  "player_data" text,
  "game_data" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references users("id") on delete cascade on update no action
);
CREATE INDEX "idx_level" on "players"("level");
CREATE INDEX "idx_location" on "players"("location_type", "location_id");
CREATE UNIQUE INDEX "players_user_id_unique" on "players"("user_id");
CREATE TABLE IF NOT EXISTS "custom_items"(
  "id" integer primary key autoincrement not null,
  "base_item_id" integer not null,
  "creator_id" integer not null,
  "custom_stats" text not null,
  "base_stats" text not null,
  "material_bonuses" text not null,
  "base_durability" integer not null,
  "max_durability" integer not null,
  "is_masterwork" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("base_item_id") references "items"("id") on delete cascade,
  foreign key("creator_id") references "players"("id") on delete cascade
);
CREATE INDEX "idx_creator_custom_items" on "custom_items"("creator_id");
CREATE INDEX "idx_base_item" on "custom_items"("base_item_id");
CREATE TABLE IF NOT EXISTS "alchemy_materials"(
  "id" integer primary key autoincrement not null,
  "item_name" varchar not null,
  "stat_bonuses" text not null,
  "durability_bonus" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "unique_material" on "alchemy_materials"("item_name");
CREATE INDEX "idx_active_admins" on "users"("is_admin", "admin_activated_at");
CREATE INDEX "idx_admin_role" on "users"("admin_role_id");
CREATE INDEX "idx_admin_level" on "users"("admin_level");
CREATE TABLE IF NOT EXISTS "admin_permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "category" varchar not null,
  "action" varchar not null,
  "display_name" varchar not null,
  "description" text,
  "required_level" integer not null default '1',
  "is_dangerous" tinyint(1) not null default '0',
  "group_name" varchar,
  "parent_permission_id" integer,
  "resource_constraints" text,
  "conditions" text,
  "localized_names" text,
  "localized_descriptions" text,
  "is_system_permission" tinyint(1) not null default '0',
  "is_active" tinyint(1) not null default '1',
  "deprecated_at" datetime,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "idx_permission_category_action" on "admin_permissions"(
  "category",
  "action"
);
CREATE INDEX "idx_permission_level_active" on "admin_permissions"(
  "required_level",
  "is_active"
);
CREATE INDEX "idx_permission_group" on "admin_permissions"("group_name");
CREATE INDEX "idx_dangerous_permissions" on "admin_permissions"(
  "is_dangerous"
);
CREATE INDEX "idx_parent_permission" on "admin_permissions"(
  "parent_permission_id"
);
CREATE UNIQUE INDEX "admin_permissions_name_unique" on "admin_permissions"(
  "name"
);
CREATE TABLE IF NOT EXISTS "admin_audit_logs"(
  "id" integer primary key autoincrement not null,
  "admin_user_id" integer not null,
  "admin_email" varchar not null,
  "admin_name" varchar,
  "action" varchar not null,
  "action_category" varchar not null,
  "description" text not null,
  "resource_type" varchar,
  "resource_id" integer,
  "resource_data" text,
  "old_values" text,
  "new_values" text,
  "request_data" text,
  "ip_address" varchar not null,
  "user_agent" text,
  "session_id" varchar,
  "request_headers" text,
  "status" varchar check("status" in('success', 'failed', 'error')) not null default 'success',
  "error_message" text,
  "response_data" text,
  "severity" varchar check("severity" in('low', 'medium', 'high', 'critical')) not null default 'medium',
  "is_security_event" tinyint(1) not null default '0',
  "requires_review" tinyint(1) not null default '0',
  "event_uuid" varchar not null,
  "event_time" datetime not null default CURRENT_TIMESTAMP,
  "tags" text,
  "batch_id" varchar,
  "parent_log_id" integer,
  "archived_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("admin_user_id") references "users"("id") on delete cascade,
  foreign key("parent_log_id") references "admin_audit_logs"("id") on delete set null
);
CREATE INDEX "idx_admin_time" on "admin_audit_logs"(
  "admin_user_id",
  "event_time"
);
CREATE INDEX "idx_action_category" on "admin_audit_logs"(
  "action_category",
  "action"
);
CREATE INDEX "idx_resource" on "admin_audit_logs"(
  "resource_type",
  "resource_id"
);
CREATE INDEX "idx_security_severity" on "admin_audit_logs"(
  "severity",
  "is_security_event"
);
CREATE INDEX "idx_event_time" on "admin_audit_logs"("event_time");
CREATE INDEX "idx_batch_operations" on "admin_audit_logs"("batch_id");
CREATE INDEX "idx_status_review" on "admin_audit_logs"(
  "status",
  "requires_review"
);
CREATE INDEX "idx_ip_address" on "admin_audit_logs"("ip_address");
CREATE UNIQUE INDEX "admin_audit_logs_event_uuid_unique" on "admin_audit_logs"(
  "event_uuid"
);
CREATE TABLE IF NOT EXISTS "admin_roles"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "display_name" varchar not null,
  "description" text,
  "level" integer not null default '1',
  "is_system_role" tinyint(1) not null default '0',
  "permissions" text not null,
  "restrictions" text,
  "can_access_analytics" tinyint(1) not null default '0',
  "can_manage_users" tinyint(1) not null default '0',
  "can_manage_game_data" tinyint(1) not null default '0',
  "can_manage_system" tinyint(1) not null default '0',
  "can_invite_admins" tinyint(1) not null default '0',
  "localized_names" text,
  "is_active" tinyint(1) not null default '1',
  "deprecated_at" datetime,
  "created_by" integer,
  "updated_by" integer,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "idx_active_roles_by_level" on "admin_roles"(
  "is_active",
  "level"
);
CREATE INDEX "idx_role_level" on "admin_roles"("level");
CREATE INDEX "idx_system_roles" on "admin_roles"("is_system_role");
CREATE UNIQUE INDEX "admin_roles_name_unique" on "admin_roles"("name");
CREATE TABLE IF NOT EXISTS "locations"(
  "id" integer primary key autoincrement not null,
  "slug" varchar not null,
  "name" varchar not null,
  "type" varchar check("type" in('town', 'road', 'dungeon')) not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "locations_slug_unique" on "locations"("slug");
CREATE TABLE IF NOT EXISTS "connections"(
  "id" integer primary key autoincrement not null,
  "from_location_id" integer not null,
  "to_location_id" integer not null,
  "direction" varchar,
  "branch_point" integer,
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("from_location_id") references "locations"("id") on delete cascade,
  foreign key("to_location_id") references "locations"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "monsters"(
  "id" varchar not null,
  "name" varchar not null,
  "level" integer not null,
  "hp" integer not null,
  "max_hp" integer not null,
  "attack" integer not null,
  "defense" integer not null,
  "agility" integer not null,
  "evasion" integer not null,
  "accuracy" integer not null,
  "experience_reward" integer not null,
  "emoji" varchar,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "monsters_level_index" on "monsters"("level");
CREATE INDEX "monsters_is_active_index" on "monsters"("is_active");
CREATE TABLE IF NOT EXISTS "monster_spawns"(
  "id" integer primary key autoincrement not null,
  "spawn_list_id" varchar not null,
  "monster_id" varchar not null,
  "spawn_rate" numeric not null,
  "priority" integer not null default '0',
  "min_level" integer,
  "max_level" integer,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("spawn_list_id") references "spawn_lists"("id") on delete cascade,
  foreign key("monster_id") references "monsters"("id") on delete cascade
);
CREATE INDEX "monster_spawns_spawn_list_id_index" on "monster_spawns"(
  "spawn_list_id"
);
CREATE INDEX "monster_spawns_monster_id_index" on "monster_spawns"(
  "monster_id"
);
CREATE INDEX "monster_spawns_is_active_index" on "monster_spawns"("is_active");
CREATE INDEX "monster_spawns_priority_index" on "monster_spawns"("priority");
CREATE UNIQUE INDEX "monster_spawns_spawn_list_id_monster_id_unique" on "monster_spawns"(
  "spawn_list_id",
  "monster_id"
);
CREATE TABLE IF NOT EXISTS "spawn_lists"(
  "id" varchar not null,
  "name" varchar not null,
  "description" text,
  "is_active" tinyint(1) not null default '1',
  "tags" text,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "spawn_lists_is_active_index" on "spawn_lists"("is_active");
CREATE TABLE IF NOT EXISTS "standard_items"(
  "id" varchar not null,
  "name" varchar not null,
  "description" text,
  "category" varchar not null,
  "category_name" varchar not null,
  "effects" text not null,
  "value" integer not null,
  "sell_price" integer,
  "stack_limit" integer not null default '1',
  "max_durability" integer,
  "is_equippable" tinyint(1) not null default '0',
  "is_usable" tinyint(1) not null default '0',
  "weapon_type" varchar,
  "is_standard" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "standard_items_category_index" on "standard_items"("category");
CREATE INDEX "standard_items_is_equippable_index" on "standard_items"(
  "is_equippable"
);
CREATE INDEX "standard_items_is_usable_index" on "standard_items"("is_usable");
CREATE INDEX "standard_items_weapon_type_index" on "standard_items"(
  "weapon_type"
);
CREATE INDEX "standard_items_is_standard_index" on "standard_items"(
  "is_standard"
);
CREATE TABLE IF NOT EXISTS "dungeons_desc"(
  "id" integer primary key autoincrement not null,
  "dungeon_id" varchar not null,
  "dungeon_name" varchar not null,
  "dungeon_desc" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "dungeons_desc_dungeon_id_index" on "dungeons_desc"("dungeon_id");
CREATE INDEX "dungeons_desc_is_active_index" on "dungeons_desc"("is_active");
CREATE UNIQUE INDEX "dungeons_desc_dungeon_id_unique" on "dungeons_desc"(
  "dungeon_id"
);
CREATE TABLE IF NOT EXISTS "routes"(
  "id" varchar not null,
  "name" varchar not null,
  "description" text,
  "category" varchar not null,
  "length" integer,
  "difficulty" varchar,
  "encounter_rate" numeric,
  "spawn_list_id" varchar,
  "is_active" tinyint(1) not null default('1'),
  "type" varchar,
  "services" text,
  "special_actions" text,
  "branches" text,
  "floors" integer,
  "min_level" integer,
  "max_level" integer,
  "boss" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "spawn_tags" text,
  "spawn_description" text,
  "dungeon_id" varchar,
  foreign key("dungeon_id") references "dungeons_desc"("dungeon_id") on delete set null on update cascade,
  primary key("id")
);
CREATE INDEX "game_locations_category_index" on "routes"("category");
CREATE INDEX "game_locations_difficulty_index" on "routes"("difficulty");
CREATE INDEX "game_locations_is_active_index" on "routes"("is_active");
CREATE INDEX "game_locations_spawn_list_id_index" on "routes"("spawn_list_id");
CREATE INDEX "game_locations_dungeon_id_index" on "routes"("dungeon_id");
CREATE TABLE IF NOT EXISTS "route_connections"(
  "id" integer primary key autoincrement not null,
  "source_location_id" varchar not null,
  "target_location_id" varchar not null,
  "connection_type" varchar not null,
  "position" integer,
  "direction" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("source_location_id") references "routes"("id") on delete cascade,
  foreign key("target_location_id") references "routes"("id") on delete cascade
);
CREATE INDEX "location_connections_connection_type_index" on "route_connections"(
  "connection_type"
);
CREATE INDEX "location_connections_direction_index" on "route_connections"(
  "direction"
);
CREATE INDEX "location_connections_source_location_id_index" on "route_connections"(
  "source_location_id"
);
CREATE INDEX "location_connections_target_location_id_index" on "route_connections"(
  "target_location_id"
);
CREATE TABLE IF NOT EXISTS "monster_spawn_lists"(
  "id" integer primary key autoincrement not null,
  "location_id" varchar not null,
  "monster_id" varchar not null,
  "spawn_rate" numeric not null,
  "priority" integer not null default('0'),
  "min_level" integer,
  "max_level" integer,
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("monster_id") references monsters("id") on delete cascade on update no action,
  foreign key("location_id") references "routes"("id") on delete cascade
);
CREATE INDEX "monster_spawn_lists_is_active_index" on "monster_spawn_lists"(
  "is_active"
);
CREATE INDEX "monster_spawn_lists_location_id_index" on "monster_spawn_lists"(
  "location_id"
);
CREATE UNIQUE INDEX "monster_spawn_lists_location_id_monster_id_unique" on "monster_spawn_lists"(
  "location_id",
  "monster_id"
);
CREATE INDEX "monster_spawn_lists_monster_id_index" on "monster_spawn_lists"(
  "monster_id"
);
CREATE INDEX "monster_spawn_lists_priority_index" on "monster_spawn_lists"(
  "priority"
);
CREATE TABLE IF NOT EXISTS "gathering_mappings"(
  "id" integer primary key autoincrement not null,
  "route_id" varchar not null,
  "item_id" integer not null,
  "required_skill_level" integer not null default '1',
  "success_rate" integer not null,
  "quantity_min" integer not null default '1',
  "quantity_max" integer not null default '1',
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("route_id") references "routes"("id") on delete cascade,
  foreign key("item_id") references "items"("id") on delete cascade
);
CREATE INDEX "idx_gathering_route_id" on "gathering_mappings"("route_id");
CREATE INDEX "idx_gathering_item_id" on "gathering_mappings"("item_id");
CREATE INDEX "idx_gathering_skill_level" on "gathering_mappings"(
  "required_skill_level"
);
CREATE INDEX "idx_gathering_active" on "gathering_mappings"("is_active");
CREATE UNIQUE INDEX "unique_route_item" on "gathering_mappings"(
  "route_id",
  "item_id"
);
CREATE TABLE IF NOT EXISTS "town_facilities"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "facility_type" varchar not null,
  "location_id" varchar not null,
  "location_type" varchar not null,
  "is_active" tinyint(1) not null default '1',
  "description" text,
  "facility_config" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "town_facilities_location_id_location_type_facility_type_unique" on "town_facilities"(
  "location_id",
  "location_type",
  "facility_type"
);

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2025_07_15_041506_create_equipment_table',1);
INSERT INTO migrations VALUES(5,'2025_07_16_045341_create_shops_table',1);
INSERT INTO migrations VALUES(6,'2025_07_16_045401_create_shop_items_table',1);
INSERT INTO migrations VALUES(7,'2025_07_17_033710_create_characters_table',1);
INSERT INTO migrations VALUES(8,'2025_07_17_034052_add_mp_and_magic_attack_to_characters_table',1);
INSERT INTO migrations VALUES(9,'2025_07_17_034143_create_items_table',1);
INSERT INTO migrations VALUES(10,'2025_07_23_043133_add_user_and_game_data_to_characters_table',1);
INSERT INTO migrations VALUES(11,'2025_07_23_043149_create_battle_logs_table',1);
INSERT INTO migrations VALUES(12,'2025_07_23_044753_create_skills_table',1);
INSERT INTO migrations VALUES(13,'2025_07_23_044805_create_active_effects_table',1);
INSERT INTO migrations VALUES(14,'2025_07_23_044815_add_level_and_experience_to_characters_table',1);
INSERT INTO migrations VALUES(15,'2025_07_23_051003_create_inventories_table',1);
INSERT INTO migrations VALUES(16,'2025_07_23_061416_create_active_battles_table',1);
INSERT INTO migrations VALUES(17,'2025_07_23_063521_add_session_tracking_to_users_table',1);
INSERT INTO migrations VALUES(18,'2025_07_26_121937_create_players_table',1);
INSERT INTO migrations VALUES(19,'2025_07_26_122032_migrate_characters_to_players_data',1);
INSERT INTO migrations VALUES(20,'2025_07_26_122143_update_foreign_keys_to_players',1);
INSERT INTO migrations VALUES(21,'2025_07_27_111752_add_gold_to_players',1);
INSERT INTO migrations VALUES(22,'2025_07_27_111952_fix_player_default_gold',1);
INSERT INTO migrations VALUES(23,'2025_07_29_043244_remove_rarity_from_items_table',1);
INSERT INTO migrations VALUES(24,'2025_07_29_081617_create_custom_items_table',1);
INSERT INTO migrations VALUES(25,'2025_07_29_081645_create_alchemy_materials_table',1);
INSERT INTO migrations VALUES(26,'2025_08_13_035226_add_admin_system_to_users_table',1);
INSERT INTO migrations VALUES(27,'2025_08_13_035226_create_admin_permissions_table',1);
INSERT INTO migrations VALUES(28,'2025_08_13_035227_create_admin_audit_logs_table',1);
INSERT INTO migrations VALUES(29,'2025_08_13_035227_create_admin_roles_table',1);
INSERT INTO migrations VALUES(30,'2025_08_14_044610_create_locations_table',1);
INSERT INTO migrations VALUES(31,'2025_08_14_044631_create_connections_table',1);
INSERT INTO migrations VALUES(33,'2025_08_19_044721_create_monsters_table',2);
INSERT INTO migrations VALUES(35,'2025_08_19_044726_create_location_connections_table',3);
INSERT INTO migrations VALUES(36,'2025_08_19_044726_create_locations_table',3);
INSERT INTO migrations VALUES(37,'2025_08_19_044726_create_monster_spawns_table',3);
INSERT INTO migrations VALUES(38,'2025_08_19_044726_create_spawn_lists_table',3);
INSERT INTO migrations VALUES(39,'2025_08_19_044727_create_standard_items_table',3);
INSERT INTO migrations VALUES(40,'2025_08_19_045330_update_location_connections_enum',4);
INSERT INTO migrations VALUES(41,'2025_08_20_024009_create_monster_spawn_lists_table',5);
INSERT INTO migrations VALUES(42,'2025_08_20_024426_add_spawn_fields_to_game_locations_table',5);
INSERT INTO migrations VALUES(43,'2025_08_20_095043_create_dungeons_desc_table',6);
INSERT INTO migrations VALUES(44,'2025_08_20_095115_add_dungeon_id_to_game_locations_table',6);
INSERT INTO migrations VALUES(45,'2025_08_21_022026_rename_game_locations_tables',7);
INSERT INTO migrations VALUES(46,'2025_08_25_024902_create_gathering_mappings_table',8);
INSERT INTO migrations VALUES(47,'2025_08_25_074151_remove_durability_from_custom_items_table',9);
INSERT INTO migrations VALUES(48,'2025_08_26_012740_create_town_facilities_table',10);
INSERT INTO migrations VALUES(49,'2025_08_26_012929_rename_shop_items_to_facility_items',11);
INSERT INTO migrations VALUES(50,'2025_08_26_013225_migrate_shops_to_town_facilities',12);
INSERT INTO migrations VALUES(51,'2025_08_26_013429_drop_shops_table',13);
