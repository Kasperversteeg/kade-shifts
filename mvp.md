i want to make a simple hour registration app. These are the
requirements:

- stack does not matter, anything but .net stuff
- should work on mobile as well as browser
- i want to do minimal fronted/css layourtwork, so maybe a css framework?
- i want to make a quick mvp to start with

the app userstories:

backend

- admin should see a overview of worked hours per month
- app should send an auto generated email with month totals
- admin can invite new user

frontend

- user can should have a dashboard
- user should be able to add work hours (simply) from dash
  - user should only put in the shift start, shift end and break. with that app should calculate the total
- user can see an overview of their work hours per month

Can you interview me for the missing details and then make a suggestion on what stacks are good for this?

Authentication & Users

How should users log in? Email/password? Google OAuth? Magic links? Or something simpler? login should be e-mail/password for now, oauth in the future maybe?
How many users are we talking about? Is this for a small team (5-10 people) or could it scale to 50+? i'm aiming for small teams, 15-20 people
User roles: Just "admin" and "user", or do you need more granular permissions? just admin and user is fine for now

Data & Storage

Data persistence: Do you need this data stored long-term (database), or would something simpler work for the MVP? i want to build on the mvp so it should store data in the database
Historical data: How far back should users be able to see their hours? Just current month, or full history? It should show per month, but they should be able to swipe or click to the previous or next month
Time zones: Do users work in different time zones, or all same location? for now just the same timezone

Email Functionality

Email frequency: Should the email be sent automatically at month-end? Or manually triggered by admin? for now triggered by an admin
Email recipients: Just admins get the summary, or do users also get their own summaries? just the admin gets an overview of all the worked hours

Mobile Experience

Mobile approach: Are you okay with a responsive web app that works on mobile browsers, or do you want a native mobile app experience (PWA)? pwa is fine with me if the app experience is good enough

Technical Preferences

Hosting: Do you have a preference? (Vercel, Netlify, Heroku, Railway, etc.) or want to keep it super simple? i'm hosting myself on a plesk server
Your experience: What languages/frameworks are you most comfortable with? (This will help me suggest something you can move fast with) I'm familiar with javascript/vue and php/laravel. but i'm open to trying new things if that helps me move quicker
