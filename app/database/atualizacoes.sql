CREATE TABLE api_call( 
      id  SERIAL    NOT NULL  , 
      class_name text   NOT NULL  , 
      method_name text   NOT NULL  , 
      fl_public boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      fl_restric_admin boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      class_url text   , 
      fl_register_parameters boolean   NOT NULL    DEFAULT 'true NOT NULL', 
      fl_register_return boolean   NOT NULL    DEFAULT 'true NOT NULL', 
      https_method text   , 
      permission text   , 
      fl_on boolean   NOT NULL    DEFAULT 'true NOT NULL', 
 PRIMARY KEY (id)) ; 

CREATE TABLE api_token( 
      id  SERIAL    NOT NULL  , 
      dt_register timestamp   NOT NULL  , 
      name text   NOT NULL  , 
      code text   NOT NULL  , 
      fl_on boolean   NOT NULL    DEFAULT 'true NOT NULL', 
      permission text   , 
      due_date date   , 
      fl_renew boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      days_due text   , 
      dt_renew timestamp   , 
      reference text   , 
      code_encrypt text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE api_token_call( 
      id  SERIAL    NOT NULL  , 
      token_id integer   NOT NULL  , 
      call_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE bas_person( 
      id  SERIAL    NOT NULL  , 
      dt_register timestamp   NOT NULL  , 
      name text   NOT NULL  , 
      code text   , 
      phone text   , 
      email text   , 
      photo text   , 
      zip_code text   , 
      street text   , 
      neighborhood text   , 
      number text   , 
      city_id text   , 
      description text   , 
      complement text   , 
      dt_update timestamp   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE bas_person_company( 
      person_id  SERIAL    NOT NULL  , 
      name_fantasy text   , 
      cnpj text   , 
      owner_id integer   , 
 PRIMARY KEY (person_id)) ; 

CREATE TABLE bas_person_individual( 
      person_id  SERIAL    NOT NULL  , 
      birth_date date   , 
      cpf text   , 
      gender char  (1)   , 
      rg text   , 
 PRIMARY KEY (person_id)) ; 

CREATE TABLE cad_event( 
      id  SERIAL    NOT NULL  , 
      name text   NOT NULL  , 
      dt_event timestamp   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE cad_subscription( 
      id  SERIAL    NOT NULL  , 
      user_id integer   NOT NULL  , 
      event_id integer   NOT NULL  , 
      dt_subscription timestamp   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_group( 
      id  SERIAL    NOT NULL  , 
      name text   NOT NULL  , 
      fl_admin boolean   NOT NULL    DEFAULT 'false NOT NULL', 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_group_screen( 
      id  SERIAL    NOT NULL  , 
      screen_id integer   NOT NULL  , 
      group_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_menu( 
      id  SERIAL    NOT NULL  , 
      name text   NOT NULL  , 
      icon text   NOT NULL  , 
      sequence integer   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_screen( 
      id  SERIAL    NOT NULL  , 
      name text   NOT NULL  , 
      controller text   NOT NULL  , 
      icon text   NOT NULL  , 
      fl_view_menu boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      fl_public boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      menu_id integer   NOT NULL  , 
      fl_admin boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      helper text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_user( 
      dt_register timestamp   NOT NULL  , 
      id  SERIAL    NOT NULL  , 
      fl_on boolean   NOT NULL    DEFAULT 'true NOT NULL', 
      code text   , 
      pip_code text   , 
      password text   , 
      fl_term boolean   NOT NULL    DEFAULT 'false NOT NULL', 
      description text   , 
 PRIMARY KEY (id)) ; 

CREATE TABLE sys_user_group( 
      id  SERIAL    NOT NULL  , 
      user_id integer   NOT NULL  , 
      group_id integer   NOT NULL  , 
 PRIMARY KEY (id)) ; 

ALTER TABLE api_token_call ADD CONSTRAINT fk_api_token_call_1 FOREIGN KEY (call_id) references api_call(id); 
ALTER TABLE api_token_call ADD CONSTRAINT fk_api_token_call_2 FOREIGN KEY (token_id) references api_token(id); 
ALTER TABLE bas_person_company ADD CONSTRAINT fk_bas_person_company_1 FOREIGN KEY (owner_id) references bas_person(id); 
ALTER TABLE bas_person_company ADD CONSTRAINT fk_bas_person_company_2 FOREIGN KEY (person_id) references bas_person(id); 
ALTER TABLE bas_person_individual ADD CONSTRAINT fk_bas_person_individual_1 FOREIGN KEY (person_id) references bas_person(id); 
ALTER TABLE cad_subscription ADD CONSTRAINT fk_cad_subscription_1 FOREIGN KEY (user_id) references sys_user(id); 
ALTER TABLE cad_subscription ADD CONSTRAINT fk_cad_subscription_2 FOREIGN KEY (event_id) references cad_evento(id); 
ALTER TABLE sys_group_screen ADD CONSTRAINT fk_sys_group_screen_1 FOREIGN KEY (group_id) references sys_group(id); 
ALTER TABLE sys_group_screen ADD CONSTRAINT fk_sys_group_screen_2 FOREIGN KEY (screen_id) references sys_screen(id); 
ALTER TABLE sys_screen ADD CONSTRAINT fk_sys_screen_1 FOREIGN KEY (menu_id) references sys_menu(id); 
ALTER TABLE sys_user ADD CONSTRAINT fk_sys_user_1 FOREIGN KEY (id) references bas_person(id); 
ALTER TABLE sys_user_access ADD CONSTRAINT fk_sys_user_access_1 FOREIGN KEY (user_id) references sys_user(id); 
ALTER TABLE sys_user_group ADD CONSTRAINT fk_sys_user_group_1 FOREIGN KEY (group_id) references sys_group(id); 
ALTER TABLE sys_user_group ADD CONSTRAINT fk_sys_user_group_2 FOREIGN KEY (user_id) references sys_user(id); 

SELECT setval('api_call_id_seq', coalesce(max(id),0) + 1, false) FROM api_call;
SELECT setval('api_token_id_seq', coalesce(max(id),0) + 1, false) FROM api_token;
SELECT setval('api_token_call_id_seq', coalesce(max(id),0) + 1, false) FROM api_token_call;
SELECT setval('bas_person_id_seq', coalesce(max(id),0) + 1, false) FROM bas_person;
SELECT setval('bas_person_company_person_id_seq', coalesce(max(person_id),0) + 1, false) FROM bas_person_company;
SELECT setval('bas_person_individual_person_id_seq', coalesce(max(person_id),0) + 1, false) FROM bas_person_individual;
SELECT setval('cad_evento_id_seq', coalesce(max(id),0) + 1, false) FROM cad_evento;
SELECT setval('cad_subscription_id_seq', coalesce(max(id),0) + 1, false) FROM cad_subscription;
SELECT setval('sys_group_id_seq', coalesce(max(id),0) + 1, false) FROM sys_group;
SELECT setval('sys_group_screen_id_seq', coalesce(max(id),0) + 1, false) FROM sys_group_screen;
SELECT setval('sys_menu_id_seq', coalesce(max(id),0) + 1, false) FROM sys_menu;
SELECT setval('sys_screen_id_seq', coalesce(max(id),0) + 1, false) FROM sys_screen;
SELECT setval('sys_user_id_seq', coalesce(max(id),0) + 1, false) FROM sys_user;
SELECT setval('sys_user_access_id_seq', coalesce(max(id),0) + 1, false) FROM sys_user_access;
SELECT setval('sys_user_group_id_seq', coalesce(max(id),0) + 1, false) FROM sys_user_group;

GRANT SELECT, UPDATE, INSERT, DELETE ON api_call TO sync;
ALTER TABLE api_call OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON api_token TO sync;
ALTER TABLE api_token OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON api_token_call TO sync;
ALTER TABLE api_token_call OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON bas_person TO sync;
ALTER TABLE bas_person OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON bas_person_individual TO sync;
ALTER TABLE bas_person_individual OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON bas_person_company TO sync;
ALTER TABLE bas_person_company OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_group_screen TO sync;
ALTER TABLE sys_group_screen OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_group TO sync;
ALTER TABLE sys_group OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_user OWNER TO sync;
ALTER TABLE sys_user OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON cad_subscription TO sync;
ALTER TABLE cad_subscription OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON cad_event TO sync;
ALTER TABLE cad_event OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_screen TO sync;
ALTER TABLE sys_screen OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_menu TO sync;
ALTER TABLE sys_menu OWNER TO sync;

GRANT SELECT, UPDATE, INSERT, DELETE ON sys_user_group TO sync;
ALTER TABLE sys_user_group OWNER TO sync;

--19/04/2022
ALTER TABLE cad_event         ADD COLUMN code         TEXT        NULL;
ALTER TABLE cad_event         ADD COLUMN description  TEXT        NULL;
ALTER TABLE cad_subscription  ADD COLUMN fl_present   BOOLEAN     NULL DEFAULT 'f'; 

--20/04/2022
CREATE TABLE cad_type_document(
  id    SERIAL    PRIMARY KEY NOT NULL,
  name            TEXT        NOT NULL,
  template        TEXT        NOT NULL,
  event_id        INT         NOT NULL,

  FOREIGN KEY (event_id) REFERENCES cad_event (id)
);

GRANT SELECT, UPDATE, INSERT, DELETE ON cad_type_document TO sync;
ALTER TABLE cad_type_document OWNER TO sync;

ALTER TABLE bas_person ADD COLUMN certificate  TEXT NULL;