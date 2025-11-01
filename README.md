# Laravel Football Tournament

Laravel application implementing Football Tournament where you can:
- create tournament, 
- insert/delete teams, 
- schedule round-robin games, 
- insert result for game, 
- check standings

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
