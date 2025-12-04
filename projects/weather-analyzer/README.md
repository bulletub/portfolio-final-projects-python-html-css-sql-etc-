# Weather Analyzer

A comprehensive real-time weather analysis dashboard with interactive maps and advanced analytics.

## Features

- 🌍 Interactive map with location selection
- 📊 Real-time weather updates (auto-refresh)
- 📈 Advanced analytics and trend analysis
- ⚠️ Automatic weather alerts
- 📥 Data export (CSV/JSON)
- 🔍 Location search with autocomplete

## Technologies

- Python 3
- Flask
- Open-Meteo API (free, no API key needed)
- Leaflet.js for maps
- Matplotlib for charts
- HTML5, CSS3, JavaScript

## Local Development

1. Install dependencies:
```bash
pip install -r requirements.txt
```

2. Run the application:
```bash
python weather_analyzer.py
```

3. Open browser:
```
http://localhost:5000
```

## Deployment

See `DEPLOYMENT_GUIDE.md` in the parent directory for deployment instructions to Render, Railway, or other platforms.

## API Endpoints

- `/api/current` - Get current weather data
- `/api/weather` - Get full weather dataset
- `/api/export` - Export weather data (CSV/JSON)
