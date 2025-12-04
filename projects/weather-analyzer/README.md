# 🌤️ Weather Analyzer - Real-Time Weather Dashboard

A comprehensive real-time weather analysis dashboard with interactive maps, advanced analytics, and data export capabilities. Built with Python Flask and modern web technologies as part of a 4-month portfolio development period.

## 🎯 What I Built

I developed a complete weather analysis platform from scratch, including:

- **Interactive Weather Map**: Click anywhere on the map to get weather data for that location
- **Real-Time Updates**: Auto-refreshing weather data with configurable intervals (30 seconds to 10 minutes)
- **Advanced Analytics**: Trend analysis, distribution charts, and statistical insights
- **Weather Alerts System**: Automatic alerts for extreme temperatures, strong winds, heavy rainfall, thunderstorms, snow, fog, and pressure changes
- **Data Export**: Export weather data as CSV or JSON files
- **Location Search**: Search for any city or location with autocomplete suggestions
- **Multiple Data Types**: Temperature, Humidity, Pressure, Precipitation, Wind Speed, Cloud Cover
- **Historical Data**: Access up to 90 days of historical weather data
- **Statistical Analysis**: Median, standard deviation, percentiles, trend direction, coefficient of variation

## 🛠️ Technologies Used

- **Python 3** - Backend programming language
- **Flask** - Lightweight web framework
- **Open-Meteo API** - Free weather data API (no API key required)
- **Leaflet.js** - Interactive map library
- **Matplotlib** - Data visualization and chart generation
- **HTML5** - Modern markup
- **CSS3** - Styling and responsive design
- **JavaScript (ES6+)** - Frontend interactivity
- **HTMX** - Dynamic content updates
- **Fetch API** - Asynchronous data fetching

## ⏱️ Development Time

This project was developed as part of a **4-month portfolio development period**, showcasing backend development skills, API integration, and data visualization capabilities.

## ✨ Key Features

- 🌍 **Interactive Map**: Click on map markers or anywhere on the map to get weather data
- 📊 **Real-Time Dashboard**: Live weather updates with configurable refresh intervals
- 📈 **Analytics**: Visual charts showing weather trends over time
- ⚠️ **Smart Alerts**: Automatic notifications for severe weather conditions
- 📥 **Data Export**: Download weather data as CSV or JSON
- 🔍 **Location Search**: Search with autocomplete for any city worldwide
- 📉 **Trend Analysis**: Visual representation of weather patterns
- 📊 **Statistics**: Comprehensive statistical analysis of weather data

## 🚀 API Endpoints

### `/api/current`
Get current weather data (JSON)
- Parameters: `lat`, `lon`, `city`, `place`
- Returns: Current conditions, alerts, timestamp

### `/api/weather`
Get full weather dataset (JSON)
- Parameters: `lat`, `lon`, `city`, `days`
- Returns: Complete weather dataset with hourly data

### `/api/export`
Export weather data
- Parameters: `lat`, `lon`, `city`, `days`, `format` (csv/json)
- Returns: CSV file or JSON response

## 💻 Local Development

### Prerequisites
- Python 3.8 or higher
- pip (Python package manager)

### Setup

1. **Install dependencies:**
   ```bash
   pip install -r requirements.txt
   ```

2. **Run the application:**
   ```bash
   python weather_analyzer.py
   ```

3. **Access the dashboard:**
   ```
   http://localhost:5000
   ```

## 🌍 Weather Data Source

This application uses the [Open-Meteo API](https://open-meteo.com/), a free and open-source weather API that provides:
- Current weather conditions
- Hourly forecasts (up to 16 days)
- Historical data (up to 90 days)
- Global coverage
- **No API key or registration required!**

## 📊 Analytics Features

- **Trend Charts**: Visual representation of weather trends over selected time periods
- **Distribution Charts**: Bar charts showing data distribution
- **Statistical Metrics**:
  - Median values
  - Standard deviation
  - 25th and 75th percentiles
  - Trend direction (Increasing/Decreasing/Stable)
  - Coefficient of variation

## ⚠️ Weather Alerts

Automatic alerts for:
- **Extreme Temperatures**: Heat warnings (>35°C) and cold warnings (<0°C)
- **Strong Winds**: Wind speed alerts (>25 km/h)
- **Heavy Rainfall**: Precipitation warnings (>10mm/hour)
- **Thunderstorms**: Lightning and storm detection
- **Snow Conditions**: Snowfall alerts
- **Fog Warnings**: Low visibility conditions
- **Pressure Changes**: Significant pressure variations

## 🗺️ Default Cities

Pre-configured cities (Philippines):
- Manila
- Cebu City
- Davao City
- Baguio City
- Iloilo City
- Cagayan de Oro
- Zamboanga City
- Tacloban City

You can add more cities by editing the `CITIES` dictionary in `weather_analyzer.py`.

## 🔄 Refresh Intervals

Available auto-refresh intervals:
- 30 seconds
- 1 minute (default)
- 5 minutes
- 10 minutes

## 📥 Data Export

Export weather data with:
- **Format Options**: CSV or JSON
- **Time Range**: Up to 90 days of historical data
- **Data Points**: All weather parameters (temperature, humidity, pressure, etc.)
- **Hourly Data**: Complete hourly dataset export

## 🚀 Deployment

See `DEPLOYMENT_GUIDE.md` in the parent directory for deployment instructions to:
- Render (recommended)
- Railway
- Heroku
- Other cloud platforms

## 📚 What I Learned

- Building RESTful APIs with Flask
- Integrating third-party APIs
- Data visualization with Matplotlib
- Interactive map integration with Leaflet.js
- Real-time data updates and auto-refresh
- Statistical analysis and data processing
- CSV/JSON data export functionality
- Responsive web design

## 🎨 UI Features

- Clean, modern dashboard design
- Interactive map with clickable locations
- Real-time data updates without page refresh
- Responsive layout for all devices
- Loading states and error handling
- Smooth animations and transitions

---

**Developed in 4 months** | **Python + Flask** | **Real-Time Weather Analytics**
