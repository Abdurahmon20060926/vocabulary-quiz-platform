# PostgreSQL Smoke Test

This procedure checks the PostgreSQL migrations and the quiz-run ownership
invariant using the local development Compose stack. Docker is unavailable in
the current environment, so the Compose workflow and production image have not
been run. The migrations and ownership behavior were separately verified on an
isolated PostgreSQL 16.15 cluster, including an existing-run upgrade, rollback
and re-application, invalid-owner rejection, and cascade deletion.

## Before You Start

- Install Docker Engine and Docker Compose v2.
- Run the commands from the repository root.
- Use only a disposable local database for `migrate:fresh`; it deletes all
  tables and data in the configured database.

The owner-constraint migration is a forward migration. It restores the
historical create-table migration and then updates the existing `user_id`
foreign key to cascade on user deletion. This prevents a deleted user from
leaving a run without either a user or guest owner.

For an existing database, back it up and inspect legacy rows before applying
the migration. The following query counts rows that would violate the
exactly-one-owner constraint:

```sql
SELECT count(*)
FROM quiz_runs
WHERE (user_id IS NULL) = (guest_token IS NULL);
```

If the count is nonzero, inspect those rows and decide how to handle them before
migrating. Do not delete or assign owners automatically; the original owner
cannot be inferred safely. Existing installations should run `migrate`, not
`migrate:fresh`.

## Fresh Local Compose Database

Create a local environment file if needed, then build and start the development
services:

```sh
cp -n .env.example .env
docker compose up -d --build
```

For a disposable database only, apply all migrations and demo seeds:

```sh
docker compose exec app php artisan migrate:fresh --seed --force
docker compose exec app php artisan migrate:status
```

To run the application's feature tests, which use SQLite in memory:

```sh
docker compose exec app php artisan test --compact
```

## Check the PostgreSQL Constraint

The following transaction inserts valid user-owned and guest-owned runs, then
confirms PostgreSQL rejects both an ownerless run and a run with both owners.
All smoke-test rows are rolled back.

```sh
docker compose exec -T db psql -v ON_ERROR_STOP=1 -U quizapp -d quizapp <<'SQL'
BEGIN;

DO $$
DECLARE
    smoke_user_id bigint;
    smoke_quiz_id bigint;
BEGIN
    INSERT INTO users (google_id, name, email, created_at, updated_at)
    VALUES ('owner-smoke-test', 'Owner Smoke Test',
            'owner-smoke-test@example.invalid', now(), now())
    RETURNING id INTO smoke_user_id;

    INSERT INTO quizzes (ulid, user_id, status, is_listed, created_at, updated_at)
    VALUES ('00000000000000000000000001', smoke_user_id, 'draft', true, now(), now())
    RETURNING id INTO smoke_quiz_id;

    INSERT INTO quiz_runs (id, quiz_id, user_id, mode, question_order)
    VALUES ('00000000000000000000000002', smoke_quiz_id, smoke_user_id, 'casual', '[]');

    INSERT INTO quiz_runs (id, quiz_id, guest_token, mode, question_order)
    VALUES ('00000000000000000000000003', smoke_quiz_id, repeat('g', 64), 'casual', '[]');

    BEGIN
        INSERT INTO quiz_runs (id, quiz_id, mode, question_order)
        VALUES ('00000000000000000000000004', smoke_quiz_id, 'casual', '[]');
        RAISE EXCEPTION 'The owner constraint accepted a run with no owner';
    EXCEPTION WHEN check_violation THEN
        RAISE NOTICE 'Ownerless run rejected as expected';
    END;

    BEGIN
        INSERT INTO quiz_runs (id, quiz_id, user_id, guest_token, mode, question_order)
        VALUES ('00000000000000000000000005', smoke_quiz_id, smoke_user_id,
                repeat('g', 64), 'casual', '[]');
        RAISE EXCEPTION 'The owner constraint accepted a run with two owners';
    EXCEPTION WHEN check_violation THEN
        RAISE NOTICE 'Run with two owners rejected as expected';
    END;
END $$;

ROLLBACK;
SQL
```

Confirm the changed foreign key and check constraint are present:

```sh
docker compose exec db psql -U quizapp -d quizapp -c \
  "SELECT conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid = 'quiz_runs'::regclass ORDER BY conname;"
```

To stop the development services without deleting the database volume:

```sh
docker compose down
```
