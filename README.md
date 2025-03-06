# Manpower Deployment
A brutally simple PHP-based versioned deployment system that needs your manpower. No magic, no complication, nothing to learn.

# How to use
Place `index.php` file in your FTP folder and visit your URL.
Then it will automatically set up the deployment and then display instructions once more.

1. Put files on `stage` folder.
2. When uploading done, then put an empty `DONE.txt` on `stage` folder. 
3. Done.

It will move everything to a timestamped version folder (vYYMMDD-HHMMSS) and serve the latest version automatically.
Needs your manpower, but that means intuitive.

# Features
- Atomic updates: Updates only apply when fully uploaded (DONE.txt).
- Safe locking: Prevents race conditions when multiple clients update simultaneously.
- Zero-downtime: Users always see a valid version.
- No broken assets: Injects <base> tag to fix relative paths.
- Version tracking: Uses newest.txt to serve the latest version dynamically.

Suggested for: Quick web deployments, personal projects, and simple static/dynamic web apps.

# Story behind
I'm new to programming world and currently learning to code from AI.
This is one of my source code that I made for my personal project : https://ljhbunker.com/cc
I found it pretty useful for Final-Final-then-TrueFinal testing on actual device where you actually need to see thigns on mobile devices.
And... I want to experience sharing my code in a public space.


Thank you.
- Junghoon Lee / lee62113@naver.com
