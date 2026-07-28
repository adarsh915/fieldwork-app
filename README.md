# fieldwork-app

This workspace contains a starter scaffold for a cross-platform React Native mobile app and a Laravel backend API with admin panel support.

## Recommended stack
- Mobile: `Expo` + `TypeScript`
- Backend: `Laravel` API + admin panel

## Why Expo?
- Best for Android/iOS cross-platform development
- Fast setup and live reload
- Works well with TypeScript
- Easier than bare React Native CLI for most apps

> Note: Bootstrap is a web framework. For React Native, use native UI libraries such as `NativeBase`, `React Native Paper`, or `React Native Elements`.

## Next steps
1. Install mobile dependencies:
   ```bash
   cd mobile
   npm install
   npx expo start
   ```

2. Install backend dependencies:
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan serve
   ```

3. Replace placeholder screens with your Figma UI design.
4. Connect the mobile app to `backend` API endpoints.
