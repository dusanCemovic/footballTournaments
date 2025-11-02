# Collaboration Notes

- I built the project using PhpStorm and its AI assistant (Junie). Because the exercise suggested a 6-hour limit, I tried to leverage AI as much as possible.
- I actually worked longer than 6 hours (around 10, I didn’t track precisely) because I wanted to practice certain parts and test them in more depth.
- For smaller checks — like reformatting collection-mapping code and spotting legacy array usage in PHP — I used ChatGPT.

## Initial setup
- I first prepared a detailed README with the requirements (see the Rules section). I invested a larger share of time here to get the constraints clear.
- Based on that, in the first iteration I asked AI to create files step by step:
  - Models with migrations, factories, and seeders
  - Then API routes and controllers
  - While generating files, I also asked AI to scaffold empty services for Schedule and Leaderboard so they are ready for later work

## Service structure and refactors
- After the initial structure was in place, I manually reformatted pieces and converted services to static usage so I could call them easily from tests and seeders. I also did a few other manual reorganizations.
- Once that part stabilized, I focused on the isolated services.
  - For both services I coded and used Junie in parallel, but I spent more time on the schedule service.
  - I implemented scheduling incrementally with my own array-rotation logic and the concept of "waves".
  - For the Leaderboard I paid extra manual attention to head-to-head (H2H).

## Edge cases and tests
- As I progressed through the algorithms, I wrote down edge cases I needed to cover. (in comments)
- Most tests were created with help from AI. I wrote more tests and spent additional time on them because I hadn’t worked with tests this extensively before and wanted the practice.
- I also created a seeder to populate match results to support testing (both unit tests and manual checks via tinker in the console).
- In parallel, I wrote edge cases as code comments to export them later.

## Documentation
- I wrote the Edge Cases and Design documents myself, then used AI to reformat and refine certain parts.
- For example, I initially wrote the design document in Serbian and then translated it with AI. My goal was mostly to standardize the format; I could have done the translation myself as well.
- Example at the end of the readme is added with AI from my database
