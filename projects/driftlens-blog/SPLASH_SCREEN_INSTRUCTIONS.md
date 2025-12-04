# DriftLens Splash Screen & Loading Screen

## 🎉 Features Implemented

### ✅ Splash Screen
- **Shows once per session** when users first visit the website
- Beautiful animated logo with floating effect
- Progress bar with glowing animation
- Smooth fade-out transition after 3 seconds

### ✅ Loading Screen
- **Appears on every navigation** when users click links or navigate between pages
- Elegant blur effect with logo and spinner
- Quick transition (300ms) for smooth user experience
- Prevents jarring page changes

### ✅ Smooth Animations
- Page fade-in effects
- Button hover animations
- Link hover effects with transform
- Smooth scrolling throughout the site

## 📁 Files Created

1. **`static/js/splash.js`** - JavaScript for splash and loading screens
2. **`static/css/splash.css`** - Styling and animations
3. **`static/images/driftlens-logo.svg`** - Default logo (placeholder)

## 🖼️ Using Your Custom Logo

### To use your custom logo image:

1. **Save your logo** as `driftlens-logo.png` or `driftlens-logo.svg`
2. **Place it** in: `static/images/driftlens-logo.png`
3. **Update the script** (if using .png instead of .svg):
   - Open `static/js/splash.js`
   - Change `.svg` to `.png` on lines 21 and 51

### Recommended Logo Specifications:
- **Format**: PNG or SVG (SVG preferred for scalability)
- **Size**: 300x300px to 500x500px
- **Background**: Transparent
- **Colors**: Should work well on dark blue gradient background

## 🎨 Customization

### Change Splash Screen Duration:
In `static/js/splash.js`, line 35:
```javascript
setTimeout(() => {
    // Change 3000 (3 seconds) to your preferred duration
}, 3000);
```

### Change Colors:
In `static/css/splash.css`:
```css
/* Splash background gradient */
background: linear-gradient(135deg, #11698E 0%, #19456B 100%);

/* Progress bar color */
background: linear-gradient(90deg, #16C0B0, #84DFDB);
```

### Disable on Specific Links:
Add class `no-loading` to any link:
```html
<a href="/page" class="no-loading">No Loading Screen</a>
```

## 🚀 How It Works

1. **First Visit**: 
   - Splash screen appears with logo animation
   - Progress bar animates for 3 seconds
   - Fades out smoothly
   - Session storage remembers it was shown

2. **Navigation**:
   - Click any link → Loading screen appears
   - Page loads in background
   - Loading screen fades out when ready
   - Smooth transition between pages

3. **Session Persistence**:
   - Splash screen shows once per browser session
   - Closing browser/tab resets it
   - Loading screen works on every navigation

## 🎯 Browser Compatibility

- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers

## 📝 Notes

- The splash screen uses `sessionStorage` to track if it's been shown
- Loading screens are skipped for external links and downloads
- All animations use CSS for smooth performance
- Fully responsive on mobile devices

---

**Enjoy your beautiful DriftLens blog platform! 🎉**

