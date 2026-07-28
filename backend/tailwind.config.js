/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          50:  '#f5f3ff',
          100: '#ede9fe',
          500: '#6366f1',
          600: '#4f46e5',
          700: '#4338ca',
        },
        surface: {
          DEFAULT: '#ffffff',
          muted:   '#f8fafc',
          border:  '#e2e8f0',
        },
        ink: {
          900: '#0f172a',
          600: '#475569',
          400: '#94a3b8',
        },
        success: { bg: '#dcfce7', text: '#15803d' },
        warning: { bg: '#fef9c3', text: '#a16207' },
        danger:  { bg: '#fee2e2', text: '#b91c1c' },
        info:    { bg: '#dbeafe', text: '#1d4ed8' },
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
      },
    },
  },
  plugins: [],
};
