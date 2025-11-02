# Design Overview

This document describes the core design of the Football Tournament application: domain models, relationships, services, and API endpoints. It supplements the README and EDGECASES documents with implementation-oriented details.

## Domain Model

We model three main entities. Team is kept as a separate model (even though not strictly required) for clarity and testability.

- Tournament
  - Fields: name, courts, match_duration_minutes, start_datetime
  - Relationships: has many Teams, has many Matches

- Team
  - Fields: name, tournament_id
  - Relationship: belongs to a Tournament
  - Note: In this simplified design a team belongs to exactly one tournament. If the requirement changes so that the same club can join multiple tournaments, Team would be decoupled from Tournament and a pivot table would be introduced.

- FootballMatch (backed by the matches table)
  - Fields (key ones): tournament_id, home_team_id, away_team_id, court_number, start_datetime, end_datetime, home_goals, away_goals, is_final, unfinalize_count
  - Relationships: belongs to Tournament; homeTeam belongs to Team; awayTeam belongs to Team

## Relationships summary
- A Tournament has many Teams and many FootballMatches.
- A Team belongs to one Tournament.
- A FootballMatch belongs to one Tournament and references two Teams (home and away).

## Services
We extract core algorithms into services for isolation and easier testing. Schedule generation and leaderboard calculation are the most complex parts, so they live outside controllers.

- ScheduleGeneratorService
  - Generates a round-robin schedule for a tournament.
  - Rules: circle method; if the team count is odd a BYE is used so each team rests exactly once; teams play at most once per round; matches are grouped into waves when there are more pairings than courts; waves/rounds are placed starting from tournament.start_datetime using match_duration_minutes; no team or court overlaps.

- LeaderboardService
  - Computes the standings for a tournament from finalised matches only.
  - Scoring: Win=3, Draw=1, Loss=0. Tie-breakers in order: head-to-head points among tied teams, goal difference (GF−GA), goals scored (GF), average earliest match start time (earlier ranks higher), and a stable fallback by team_id.

- MatchFinishedService
  - Test helper that populates random final results for scheduled matches. It can finalize either a random subset or all matches in a tournament; existing finals are respected.

All services are covered by unit/integration tests where relevant.

## Persistence
All models have migrations and factories/seeders. A helper seeder can be used during tests to populate match results via MatchFinishedService.

## API and Controllers
All endpoints return JSON.

1) Create tournament
   - POST /api/tournaments
   - Validates input, creates a Tournament, and returns it.

2) Add team to tournament
   - POST /api/tournaments/{tournament}/teams
   - Validates input (team name unique within tournament), creates a Team under the Tournament, and returns it.

3) Delete team from tournament
   - DELETE /api/tournaments/{tournament}/teams/{team}
   - Allowed only if the tournament has no matches with final results; otherwise returns 422.
   - After deletion: if the tournament already had a schedule (matches exist) and there are no finals, the schedule is regenerated for the remaining teams.
   - Important note: If business rules later allow deleting a team when other teams already have final results, we would need to “award” remaining fixtures (e.g., 3–0) rather than rescheduling. This is noted in README under Important notice.

4) Generate schedule for a tournament
   - POST /api/tournaments/{tournament}/schedule:generate
   - Precondition: no match in the tournament can be final; otherwise returns 422.
   - Calls ScheduleGeneratorService::generateForTournament($tournament).

5) Submit match result
   - POST /api/matches/{match}/result
   - Validates home_goals and away_goals as non-negative integers.
   - If the match is already final, returns 422. Otherwise saves goals and marks the match as final (is_final=true).

6) Unfinalize a match result
   - POST /api/matches/{match}/unfinalize
   - Allowed only if the match is final and unfinalize_count < 1. Otherwise returns 422.
   - Clears goals, marks the match as not final, and increments unfinalize_count.

7) Get tournament leaderboard
   - GET /api/tournaments/{tournament}/leaderboard
   - Returns standings computed by LeaderboardService.

## Scheduling details (implementation notes)
- Uses the circle method; number of rounds is (team_count − 1) with a BYE if the count is odd. We keep the first team fixed and rotate the rest to produce rounds.
  - first round 1,2,3,4,5,6 so games are  1-6, 2-5, 3-4 
  - second round 1,6,2,3,4,5 so games are  1-5, 6-4, 2-3
- If there is odd number, then we add NULL at the end of the array, so in first round, first one is not playing.
- For each round, pairings are assigned to courts. If pairings exceed court count, they are played in WAVES within the round.
- Time slots are sized by match_duration_minutes; next round starts after all waves of the current round plus a small round break.
- The schedule generator ensures:
  - A team appears at most once per round and has no overlapping matches.
  - Courts are never double-booked.
  - Court numbers are always within [1, tournament.courts].

## Leaderboard details (implementation notes)
- Only final matches are included.
- Per-team aggregates tracked: played, wins, draws, losses, GF, GA, GD, points, and average start time for tie-breaks.
- Tie-break application: first filter down to tied groups, resolve with head-to-head points among the group; if still tied, compare GD, then GF, then avg earliest start, then stable ID fallback.
