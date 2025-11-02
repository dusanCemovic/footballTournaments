# EDGE CASES (Risk-Based)

This document lists prioritized edge cases for the Football Tournament system. 
Each case includes a short title, rationale, expected behavior, 

- and individual ratings for 
  - Impact (I), (High / Medium / Low)
  - Likelihood (L), (High / Medium / Low)
  - Detectability (D). (High / Medium / Low) (how easily failures would be noticed before release): High / Medium / Low (lower is riskier)
  - Priority is determined primarily by Impact on system correctness and safety, then Likelihood and Detectability.
 
The top-3 are covered by property-based tests (invariants). See README mapping for exact test references.

## Priority 1 — Schedule invariant: no team has overlapping matches (Property)
- Why important: Overlapping appearances break core scheduling feasibility and fairness.
- Expected behavior: For any generated schedule, a team cannot be assigned to two matches whose time intervals overlap.
- Ratings: I=High, L=Medium, D=Low

## Priority 2 — Schedule invariant: courts are never double-booked at the same time (Property)
- Why important: A single court cannot host two matches simultaneously; this corrupts the schedule.
- Expected behavior: For each court_number, match intervals must not overlap.
- Ratings: I=High, L=Medium, D=Medium

## Priority 3 — Leaderboard invariants: points formula and dominance (Property)
- Why important: Standings integrity depends on consistent point calculations and correct ordering.
- Expected behavior:
  - Points = 3*wins + 1*draws + 0*losses for each team.
  - A team that wins all of its final matches ranks first.
- Ratings: I=High, L=Medium, D=Medium

## Priority 4 — Leaderboard tiebreakers stability
- Why important: Deterministic ordering avoids confusing, shifting tables.
- Expected behavior: On equal points, apply in order: head-to-head points, goal difference, goals scored, earlier average start time; stable fallback by team_id.
- Ratings: I=Medium, L=Medium, D=Medium

## Priority 5 — Round-robin completeness: each pair plays exactly once
- Why important: Core competition format requires all unique pairings exactly once.
- Expected behavior: With N teams, total matches = N*(N-1)/2 and each unordered pair appears exactly once.
- Ratings: I=Medium, L=Medium, D=Medium

## Priority 6 — BYE handling for odd team counts
- Why important: Scheduling must handle odd team counts by ensuring each team has exactly one BYE.
- Expected behavior: No team is scheduled more than once per round; with odd counts, each team has one round off across the tournament.
- Ratings: I=Medium, L=Medium, D=Medium

## Priority 7 — Regeneration blocked when any final result exists
- Why important: Regenerating schedules with final results would invalidate historical outcomes.
- Expected behavior: POST /tournaments/{id}/schedule:generate returns 422 if any match is_final=true.
- Ratings: I=Medium, L=Medium, D=High

## Priority 8 — Team name uniqueness within tournament
- Why important: Duplicate team names in same tournament corrupt identity and fixtures.
- Expected behavior: Validation fails (422) if a team name already exists within the tournament.
- Ratings: I=Medium, L=Medium, D=High

## Priority 9 — Non-existent IDs via API return JSON 404
- Why important: Clear API semantics and security posture;.
- Expected behavior: Using non-existent {tournament}, {team}, or {match} returns JSON 404 via global handler.
- Ratings: I=Medium, L=Medium, D=High

## Priority 10 — Result immutability after final + single unfinalize allowed
- Why important: Protects integrity of recorded results while allowing a single correction.
- Expected behavior:
    - Entering new result on a final match returns 422.
    - Unfinalize allowed once; second attempt returns 422.
- Ratings: I=Medium, L=Medium, D=High

## Priority 11 — Negative goals validation
- Why important: Invalid numeric inputs break domain rules and can skew standings.
- Expected behavior: home_goals and away_goals must be integers >= 0; otherwise 422.
- Ratings: I=Medium, L=Low, D=High

## Priority 12 — Team deletion blocked if team has any final matches
- Why important: Prevents orphaning historical results.
- Expected behavior: DELETE returns 422 if the team has final matches in the tournament.
- Ratings: I=Medium, L=Low, D=High

## Priority 13 — Courts bound to configured range (1..courts)
- Why important: Assigning courts outside configured capacity causes operational conflicts.
- Expected behavior: All scheduled matches have court_number between 1 and tournament.courts.
- Ratings: I=Medium, L=Low, D=High
