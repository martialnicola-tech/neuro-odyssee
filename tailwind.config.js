module.exports = {
  content: ['./*.html', './js/**/*.js'],
  theme: {
    extend: {
      colors: {
        'green-deep': '#1E6B5E', 'green-mid': '#2D8C7A', 'blue-ocean': '#3B9BB0',
        'gold': '#F0A500', 'cream': '#F8F6F1', 'dark': '#1a2332'
      },
      fontFamily: {
        'serif': ['Cormorant Garamond', 'Georgia', 'serif'],
        'sans': ['Inter', 'system-ui', 'sans-serif']
      }
    }
  }
}
