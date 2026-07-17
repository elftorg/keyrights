CREATE TABLE IF NOT EXISTS dr_kr_item (
  id SERIAL PRIMARY KEY,
  entity_id INTEGER NULL,
  section_id INTEGER NULL,
  owner INTEGER NOT NULL
);

CREATE UNIQUE INDEX IF NOT EXISTS ux_dr_kr_item_entity ON dr_kr_item (entity_id);
CREATE UNIQUE INDEX IF NOT EXISTS ux_dr_kr_item_section ON dr_kr_item (section_id);
CREATE INDEX IF NOT EXISTS ix_dr_kr_item_owner ON dr_kr_item (owner);

CREATE TABLE IF NOT EXISTS dr_kr_right (
  id SERIAL PRIMARY KEY,
  item_id INTEGER NOT NULL,
  edit INTEGER NOT NULL,
  blocked INTEGER NOT NULL,
  timed TIMESTAMP NULL,
  "user" INTEGER NULL,
  "group" INTEGER NULL
);

CREATE INDEX IF NOT EXISTS ix_dr_kr_right_item_id ON dr_kr_right (item_id);
CREATE INDEX IF NOT EXISTS ix_dr_kr_right_user ON dr_kr_right ("user");
CREATE INDEX IF NOT EXISTS ix_dr_kr_right_group ON dr_kr_right ("group");
CREATE INDEX IF NOT EXISTS ix_dr_kr_right_timed ON dr_kr_right (timed);
