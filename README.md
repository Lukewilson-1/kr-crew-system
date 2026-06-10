# KR Crew System

A Kenya Railways crew booking application for depot officers and HQ users.

## Overview

This web app manages crew status, daily bookings, monthly position registers, rest countdowns, and utilization reporting. It supports live Firestore syncing, demo mode, and archived monthly exports.

## Features

- Dashboard view for crew status snapshot
- Depot-specific roster and monthly register
- Rest countdowns with auto-promote for driver rest completion
- Reports for daily status, monthly register, absences, and utilization
- Firebase Firestore backend with `.env` configuration
- Offline demo mode for local testing

## Files

- `KR_Crew_Live_v4.html` - main HTML entry point
- `app.js` - application logic, rendering, Firebase sync, and exports
- `firebase.js` - Firebase initialization and `.env` loader
- `helpers.js` - utility functions (CSV download, time formatting, search, etc.)
- `styles.css` - app styling
- `.gitignore` - repository ignore rules

## Setup

1. Clone the repository:
   ```bash
   git clone https://github.com/Lukewilson-1/kr-crew-system.git
   cd kr-crew-system
   ```

2. Add Firebase configuration in a `.env` file at the project root:
   ```text
   FIREBASE_API_KEY=your_api_key
   FIREBASE_AUTH_DOMAIN=your_project.firebaseapp.com
   FIREBASE_PROJECT_ID=your_project_id
   FIREBASE_STORAGE_BUCKET=your_project.appspot.com
   FIREBASE_MESSAGING_SENDER_ID=your_messaging_sender_id
   FIREBASE_APP_ID=your_app_id
   ```

3. Serve the project from a local web server and open `KR_Crew_Live_v4.html`.

   You can use any static server. For example, if Python is available:
   ```bash
   python -m http.server 8000
   ```

4. Open the browser at `http://localhost:8000/KR_Crew_Live_v4.html`.

## Firebase Notes

- The app loads Firestore config from `.env` using `firebase.js`.
- If no Firebase connection is available, use the offline demo mode from the login screen.
- The app seeds default user accounts, status metadata, and depot crew data when Firestore is empty.

## Running

- Sign in with an HQ or depot account.
- HQ users can view all depots and archived monthly exports.
- Depot officers can manage crew statuses and day-by-day assignments.

## GitHub

This repository is hosted at: https://github.com/Lukewilson-1/kr-crew-system

## License

Use and adapt this project as needed for Kenya Railways crew management.
