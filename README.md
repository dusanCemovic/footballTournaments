# Laravel Football Tournament

Laravel application implementing Football Tournament where you can:

- create tournament,
- insert/delete teams,
- schedule round-robin games,
- insert result for game,
- check standings

## Requirements

- Used PHP v8.3.6 & Laravel 12.36.1 (i was working on it, so maybe less)
- Mysql 8+
- Composer

1. Clone repo https://github.com/dusanCemovic/footballTournaments and go into folder `cd footballTournaments`
2. Composer:
   ```
    composer install
   ```
3. COPY env example
   ```
    cp .env.example .env
   ```
4. Run generating key:
   ```
   php artisan key:generate
   ```
5. Create file for sqlite:
   ```
   touch database/database.sqlite
   ```
6. Run Migration:
   ```
   php artisan migrate:fresh --seed
   ```
   + If you wish to run seeder for populating results you may uncomment `FinishingMatchSeeder` in `DatabaseSeeder` or just use artisan command:
     ```
     php artisan db:seed --class=FinishingMatchSeeder
     ```
7. Start Server:
    ```
    php artisan serve
    ```
8. Run tests:
    ```
    php artisan test
    ```    

# Rules:

- Tournament creation
    - has multiple teams,
    - is played in a round-robin format,
    - uses one or more courts in parallel (1-4),
    - tracks results and keeps a scoreboard according to specific rules
    - Endpoint:
        - POST /tournaments (only new tournament)
- Team management
    - Team names must be unique within a single tournament.
    - If the number of teams is odd, a 'bye' round is added – a round in which the team does not play.
    - A team can only be deleted if there are no matches with results yet.
    - Endpoint:
        - POST /tournaments/{id}/teams – dodaj ekipo
        - DELETE /tournaments/{id}/teams/{teamId} – odstrani ekipo
- Match schedule generation (round-robin)
    - Use available courts in parallel in time slots.
    - The length of the slot is specified by match_duration_minutes.
    - The first slots start at start_datetime.
    - A team may not play on two courts at the same time.
    - Each round if with different teams
    - If odd number, then one team need to wait
    - If the schedule already exists and any match contains a final result, re-generation is not allowed.
    - Between round we have some pause (20minutes)
    - Endpoint:
        - POST /tournaments/{id}/schedule:generate
- Result entry + unfinalization
    - home_goals and away_goals must be non-negative integers.
    - Once the result is entered, the match is marked as final and may not be changed again.
    - Each match can be unfinalized at most once.
    - A second unfinalization must return an error.
    - Endpoint:
        - POST /matches/{matchId}/result
        - POST /matches/{matchId}/unfinalize
- Leaderboard calculation
    - Scoring:
        - Win = 3 points
        - Draw = 1 point
        - Loss = 0 points
    - Additional ranking rules:
        - Head-to-head results between tied teams
        - Goal difference (GF − GA)
        - Goals scored (GF)
        - Average earliest start time of matches (earlier = higher place)
    - Endpoint:
        - GET /tournaments/{id}/leaderboard

- General rules:
    - Each team plays each other once - Every-against-each system
    - No overlapping matches - A team cannot play on two courts at the same time
    - Odd number of teams - Automatically adds a break
    - Number of courts - 1-4 parallel courts
    - Regeneration - Forbidden if there is a final score
    - Input data checking - No negative values
    - Score unlocking - Only allowed once
    - Ranking - Calculated according to the rules above

## Important notice:

- Team can't be deleted if it's tournament has at least one finished result. In case that team can be deleted only if
  team's game is not finished but some tournament game can be finished -> we will have to add additionally things like
  automatic 3-0 results because reschedule is not allowed. (In this exam, we won't check team separately)
- If some team is deleted after schedule is done, we will reschedule games.

## Edge cases and tests mapping

Below is the mapping between prioritized edge cases (see EDGECASES.md) and the automated tests that cover them.

| Priority | Edge case (short)                              | Test(s)                                                                                                                     | Type                 |
|----------|------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------|----------------------|
| P1       | Schedule: no team overlap                      | tests/Unit/ScheduleInvariantsTest.php::test_schedule_invariants_property_based                                              | Property / Invariant |
| P2       | Schedule: no court double-booking              | tests/Unit/ScheduleInvariantsTest.php::test_schedule_invariants_property_based                                              | Property / Invariant |
| P3       | Leaderboard: points formula and dominance      | tests/Unit/LeaderboardInvariantsTest.php::test_leaderboard_points_and_dominance_invariants                                  | Property / Invariant |
| P4       | Leaderboard tie-breaker stability              | tests/Unit/LeaderboardServiceTest.php (tie-breakers)                                                                        | Unit                 |
| P5       | Round-robin completeness (N*(N-1)/2)           | tests/Feature/ScheduleGenerationTest.php::test_generate_creates_round_robin_schedule; tests/Unit/ScheduleInvariantsTest.php | Feature + Property   |
| P6       | BYE handling for odd teams                     | tests/Unit/ScheduleInvariantsTest.php::test_schedule_invariants_property_based (random odd team counts)                     | Property             |
| P7       | Team deleted on already created matches        | tests/Feature/TeamRoutesTest.php::test_reschedule_on_team_delete_when_no_finals_and_schedule_exists                         | Feature              |
| P8       | Regeneration blocked when any final exists     | tests/Feature/ScheduleGenerationTest.php::test_regeneration_blocked_if_any_match_is_final                                   | Feature              |
| P9       | Non-existent IDs return JSON 404               | tests/Feature/TeamRoutesTest.php (nonexistent tournament 404)                                                               | Feature              |
| P10      | Result immutability; single unfinalize allowed | tests/Feature/MatchRoutesTest.php (result entry and unfinalize scenarios)                                                   | Feature              |
| P11      | Negative goals validation                      | tests/Feature/MatchRoutesTest.php (validation errors)                                                                       | Feature              |
| P12      | Team deletion blocked with final               | tests/Feature/TeamRoutesTest.php (delete team blocked 422)                                                                  | Feature              |
| P13      | Courts within configured range                 | tests/Unit/ScheduleInvariantsTest.php::test_schedule_invariants_property_based                                              | Property             |

See EDGECASES.md for details (title, rationale, expected behavior, and risk ratings) for each case.
+ I added another one (to be 13) because I changed them while I was working and those 12 and 13 are similar, but both should be mentioned, so i didn't delete


## Example of Tournament

Below is an illustrative snapshot of how a real tournament (ID = 1) could look in this system after scheduling and entering final results. This is an example to guide understanding; your actual data will depend on what you create via the API/UI.

Tip: To fetch real data from your instance, use:
- GET /api/tournaments/1/leaderboard

### Matches (Tournament ID: 1)

Assume 4 teams (Lions, Bears, Tigers, Wolves), 2 courts, 30-minute matches starting at 2025-11-05 10:00 with a short round break.

| ID | Court | Start               | End                 | Home   | Away   | Score | Final |
|----|-------|---------------------|---------------------|--------|--------|-------|-------|
| 1  | 1     | 2025-11-05 10:00    | 2025-11-05 10:30    | Lions  | Tigers | 2–1   | true  |
| 2  | 2     | 2025-11-05 10:00    | 2025-11-05 10:30    | Bears  | Wolves | 1–0   | true  |
| 3  | 1     | 2025-11-05 10:50    | 2025-11-05 11:20    | Lions  | Bears  | 1–1   | true  |
| 4  | 2     | 2025-11-05 10:50    | 2025-11-05 11:20    | Tigers | Wolves | 2–2   | true  |
| 5  | 1     | 2025-11-05 11:40    | 2025-11-05 12:10    | Wolves | Lions  | 0–3   | true  |
| 6  | 2     | 2025-11-05 11:40    | 2025-11-05 12:10    | Tigers | Bears  | 0–2   | true  |

Notes:
- Schedule uses round-robin circle method; courts are assigned per wave and rounds are separated by a small break.
- Times are examples; your schedule depends on start_datetime, match_duration_minutes, and courts.

### Leaderboard (Tournament ID: 1)

Computed from final matches only (Win=3, Draw=1, Loss=0; tie-breakers: H2H, GD, GF, avg start time).

| Pos | Team   | P | W | D | L | GF | GA | GD | Pts |
|-----|--------|---|---|---|---|----|----|----|-----|
| 1   | Lions  | 3 | 2 | 1 | 0 |  6 |  2 | +4 |  7  |
| 2   | Bears  | 3 | 2 | 1 | 0 |  4 |  1 | +3 |  7  |
| 3   | Tigers | 3 | 0 | 1 | 2 |  3 |  6 | -3 |  1  |
| 4   | Wolves | 3 | 0 | 1 | 2 |  2 |  6 | -4 |  1  |

Explanation:
- Lions and Bears tie on 7 points; their head-to-head is a draw, so goal difference (GD) decides: Lions (+4) rank above Bears (+3).
- Tigers and Wolves tie on 1 point; GD decides Tigers (-3) above Wolves (-4).

