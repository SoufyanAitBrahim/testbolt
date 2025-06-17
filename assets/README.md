# Sushi Website Assets Structure

This document describes the organized asset structure for the sushi restaurant website.

## 📁 Directory Structure

```
assets/
├── backend/                    # Admin/Backend specific files
│   ├── css/                   # Backend stylesheets
│   │   ├── dashboard.css      # Admin dashboard styles
│   │   ├── reservations.css   # Reservations management styles
│   │   └── ...               # Other admin page styles
│   └── js/                    # Backend JavaScript
│       ├── dashboard.js       # Dashboard functionality
│       ├── reservations.js    # Reservations management
│       └── ...               # Other admin scripts
├── frontend/                   # Client/Frontend specific files
│   ├── css/                   # Frontend stylesheets
│   │   ├── global.css         # Global frontend styles
│   │   ├── home.css          # Homepage specific styles
│   │   ├── menu.css          # Menu page specific styles
│   │   └── ...               # Other page styles
│   └── js/                    # Frontend JavaScript
│       ├── global.js          # Global frontend functionality
│       ├── home.js           # Homepage specific scripts
│       ├── menu.js           # Menu page specific scripts
│       └── ...               # Other page scripts
├── lib/                       # Third-party libraries
│   └── bootstrap/             # Local Bootstrap files
│       ├── css/              # Bootstrap CSS (organized by sections)
│       │   ├── bootstrap.css  # Main import file
│       │   ├── 01-variables.css
│       │   ├── 02-reboot.css
│       │   ├── 03-typography.css
│       │   ├── 04-grid.css
│       │   └── ...           # Other Bootstrap sections
│       └── js/               # Bootstrap JavaScript (organized by components)
│           ├── bootstrap.js   # Main import file
│           ├── 01-core.js
│           ├── 02-event-handler.js
│           ├── 03-dom-utilities.js
│           ├── 04-alert.js
│           ├── 05-button.js
│           └── ...           # Other Bootstrap components
└── old/                       # Legacy/backup files
    ├── style.css             # Original stylesheet
    └── script.js             # Original JavaScript
```

## 🎯 Key Features

### 1. **Automatic Page-Specific Loading**
- CSS and JS files are automatically loaded based on the current page name
- Example: `home.php` automatically loads `assets/frontend/css/home.css` and `assets/frontend/js/home.js`

### 2. **Organized Bootstrap Structure**
- Bootstrap CSS split into logical sections for easy customization
- Bootstrap JS split into individual components
- Local files instead of CDN for full control

### 3. **Frontend vs Backend Separation**
- Clear separation between client-facing and admin styles/scripts
- Different design systems for different user types

### 4. **Modular Architecture**
- Each page can have its own specific styles and functionality
- Global files for shared functionality
- Easy to maintain and extend

## 🔧 Usage

### Frontend Pages
Frontend pages automatically load:
1. `assets/lib/bootstrap/css/bootstrap.css` - Bootstrap framework
2. `assets/frontend/css/global.css` - Global frontend styles
3. `assets/frontend/css/{page-name}.css` - Page-specific styles (if exists)
4. `assets/lib/bootstrap/js/bootstrap.bundle.min.js` - Bootstrap JavaScript
5. `assets/frontend/js/global.js` - Global frontend functionality
6. `assets/frontend/js/{page-name}.js` - Page-specific scripts (if exists)

### Backend Pages
Backend pages should include:
1. `../assets/lib/bootstrap/css/bootstrap.css` - Bootstrap framework
2. `../assets/backend/css/dashboard.css` - Backend styles
3. `../assets/lib/bootstrap/js/bootstrap.bundle.min.js` - Bootstrap JavaScript
4. `../assets/backend/js/dashboard.js` - Backend functionality

## 🎨 Customization

### Bootstrap Customization
Bootstrap is now fully local and editable:
1. **Main CSS**: `assets/lib/bootstrap/css/bootstrap-main.css` - imports minified Bootstrap + custom overrides
2. **Minified CSS**: `assets/lib/bootstrap/css/bootstrap.min.css` - complete Bootstrap CSS (editable)
3. **JavaScript**: `assets/lib/bootstrap/js/bootstrap.js` - complete Bootstrap JS (editable)
4. **Custom sections**: `assets/lib/bootstrap/css/02-reboot.css` - for custom reboot overrides
5. **Easy customization**: Edit variables and styles directly in the files

### Adding New Pages
1. Create `assets/frontend/css/{page-name}.css` for page-specific styles
2. Create `assets/frontend/js/{page-name}.js` for page-specific functionality
3. Files will be automatically loaded when the page is accessed

### Global Changes
- Edit `assets/frontend/css/global.css` for frontend-wide changes
- Edit `assets/frontend/js/global.js` for frontend-wide functionality
- Edit `assets/backend/css/dashboard.css` for admin-wide changes

## 📱 Responsive Design
All stylesheets include responsive breakpoints:
- Mobile: `max-width: 576px`
- Tablet: `max-width: 768px`
- Desktop: `min-width: 992px`

## 🚀 Performance Benefits
- **Local Bootstrap files** for complete control and offline development
- **Editable source code** - modify Bootstrap directly to your needs
- **Modular page-specific loading** prevents unnecessary code
- **Organized structure** for better caching and maintenance
- **No external dependencies** - everything works offline

## 🔄 Migration from Old Structure
- **Original files moved** to `assets/old/` for backup
- **CDN links replaced** with local Bootstrap files
- **Automatic loading system** implemented
- **Complete Bootstrap source** available for editing
- **No functionality lost** in migration

## 📝 Notes
- Font Awesome still loaded from CDN (can be localized if needed)
- All Bootstrap components available and functional
- Easy to add new libraries to `assets/lib/`
- Maintains backward compatibility
