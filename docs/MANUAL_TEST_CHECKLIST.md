# Manual Test Checklist

Use this checklist in a browser against a local or staging deployment. Automated feature tests cover server-side contracts; these checks cover real browser behavior and responsive controls.

## Sign-in and navigation

- [ ] Sign in with Google as a new account. Confirm the account is created and the avatar menu appears.
- [ ] Sign in again with the same Google account after changing its display name or avatar. Confirm the existing account is updated rather than duplicated.
- [ ] Sign out and confirm protected Creator Studio and My Plays pages require sign-in.
- [ ] As a guest, confirm the sidebar offers sign-in instead of recent plays.
- [ ] Use the top-bar search from Explore and from another page. Submit with Enter and the search button; confirm the Explore results and query reflect the search.
- [ ] On Explore, search both quiz titles and descriptions. Check All, Strict, and Casual filters and Popular, Most Played, and Newest sorting.
- [ ] Check the top bar and Explore filters at desktop and mobile widths.

## Quiz visibility and lifecycle

- [ ] Confirm published, listed quizzes appear in Explore, while unlisted quizzes remain reachable only by their direct link.
- [ ] Confirm a draft and an unpublished quiz are visible and playable only by their creator.
- [ ] Confirm other users and guests see an unavailable page or not-found response for creator-only quizzes.
- [ ] Save an incomplete draft, including a partially completed question. Reopen it and confirm the incomplete values persist.
- [ ] Attempt to publish drafts with fewer than 5 or more than 15 questions, missing quiz fields, incomplete questions, duplicate options (including case/whitespace variants), and invalid timer values. Confirm publishing is refused.
- [ ] Publish a valid quiz as listed, then unpublish it. Confirm the creator can still view and test-play it, while other players cannot start new runs.
- [ ] Start a player run before unpublishing. Confirm the run becomes abandoned on that player's next run request and that the quiz is marked unavailable in their history.
- [ ] Confirm a published quiz cannot be edited or deleted, and only drafts can be deleted.
- [ ] Duplicate a published or unpublished quiz. Confirm the copy is a draft with no inherited runs, votes, or analytics, and the title including “(copy)” is at most 80 characters.
- [ ] Confirm draft quizzes do not offer Duplicate.

## Strict gameplay

- [ ] Start a strict quiz and confirm the 3-2-1 countdown appears before the first question.
- [ ] Inspect the start response in browser developer tools. Confirm it contains only the first question and displayed options, not answer data or future questions.
- [ ] Confirm question and option order are shuffled between runs.
- [ ] Answer correctly and confirm the next question arrives with a fresh timer. Answer incorrectly and confirm the run ends immediately without revealing the correct answer.
- [ ] Let the timer expire and confirm the run ends as a timeout. Switch tabs while a question is active and confirm focus loss ends the run.
- [ ] Switch tabs during countdown, checking, and result screens; confirm these states do not trigger focus-loss failure.
- [ ] Confirm the displayed timer pauses during the strict answer request and that the server applies the configured grace period.
- [ ] Exercise the answer endpoint with a missing input, both answer/reason inputs, an invalid reason, and an index outside 0–3. Confirm each is rejected without advancing or recording an answer.
- [ ] Simulate a failed answer request. Confirm the interface reports that the result could not be confirmed instead of continuing with a potentially stale question or showing a fabricated failure.

## Casual gameplay and guest ownership

- [ ] Start a casual quiz and confirm all questions load, are shuffled on the client, and options are shuffled.
- [ ] Confirm a correct answer advances immediately; a wrong answer, timeout, or focus loss ends the run.
- [ ] Submit a completed run and confirm the server-derived result matches the actual answers.
- [ ] Inspect the casual submission payload. Confirm it contains every question ID exactly once, in the client's play order, with only untouched trailing placeholders after sudden death.
- [ ] Submit a duplicated or missing question ID and confirm the server rejects the log without finishing the run.
- [ ] Confirm a guest run is tied to its guest token: the matching token can submit it, while a different token cannot.
- [ ] Submit a finished run a second time and confirm it is not counted twice.
- [ ] Confirm casual results are labeled self-reported in the quiz analytics.

## Voting, suggestions, history, and analytics

- [ ] Confirm signed-in users can toggle a vote on another user's published, listed quiz and that the count changes once per toggle.
- [ ] Confirm guests, quiz creators, and users viewing draft, unpublished, or unlisted quizzes cannot vote.
- [ ] Confirm suggestions exclude the current quiz, unpublished/unlisted quizzes, and quizzes the signed-in viewer has passed. Confirm no creator contributes more than three suggestions.
- [ ] Confirm My Plays contains only the signed-in user's attempts and is sorted most-recent first.
- [ ] Confirm attempts, best result, pass status, and first-pass date update after a run. Confirm an unpublished quiz appears unavailable without losing prior history.
- [ ] Confirm creator test runs do not affect public run/pass/reached statistics, per-question analytics, or player counts.
- [ ] Confirm guest runs are grouped into a single Guests row in Creator Studio statistics.
- [ ] Confirm the sound mute choice persists after reload and the game-loop, fail, and win sound slots behave as expected.

## Responsive gameplay controls

- [ ] On a desktop with a fine pointer, confirm W is centered above A/S/D, the matching key hints are shown, and W/A/S/D select the corresponding displayed option.
- [ ] Confirm Enter starts or retries a run and that no 1–4 or A–D keyboard shortcuts are active.
- [ ] On a touch/coarse-pointer device, confirm options use a 2×2 grid with no hotkeys or key hints.
