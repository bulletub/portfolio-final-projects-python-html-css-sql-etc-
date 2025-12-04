# weather_analyzer.py  — Real-time Weather Analyzer with Open-Meteo API
from flask import Flask, render_template, request, jsonify
import datetime
import io
import base64
import requests
import math
import json
from functools import wraps

from matplotlib.figure import Figure

app = Flask(__name__, static_folder="static", template_folder="templates")
app.config['JSONIFY_PRETTYPRINT_REGULAR'] = True

# -----------------------------
# Data & helpers
# -----------------------------
CITIES = {
    "manila":   {"lat": 14.5995, "lon": 120.9842, "label": "Manila", "region": "Metro Manila"},
    "cebu":     {"lat": 10.3157, "lon": 123.8854, "label": "Cebu City", "region": "Central Visayas"},
    "davao":    {"lat":  7.1907, "lon": 125.4553, "label": "Davao City", "region": "Davao Region"},
    "baguio":   {"lat": 16.4023, "lon": 120.5960, "label": "Baguio City", "region": "Cordillera"},
    "iloilo":   {"lat": 10.7202, "lon": 122.5621, "label": "Iloilo City", "region": "Western Visayas"},
    "cagayan":  {"lat":  8.4542, "lon": 124.6319, "label": "Cagayan de Oro", "region": "Northern Mindanao"},
    "zamboanga":{"lat":  6.9214, "lon": 122.0790, "label": "Zamboanga City", "region": "Zamboanga Peninsula"},
    "tacloban": {"lat": 11.2444, "lon": 125.0020, "label": "Tacloban City", "region": "Eastern Visayas"},
}

def _get_region_info(lat, lon, name):
    """Determine region information based on coordinates or city name."""
    # Check if it's a known city
    for city_data in CITIES.values():
        if abs(city_data["lat"] - lat) < 0.1 and abs(city_data["lon"] - lon) < 0.1:
            return city_data.get("region", "Philippines")
    
    # Determine region by approximate coordinates for Philippines
    if 14.0 <= lat <= 19.0 and 120.0 <= lon <= 122.0:
        return "Northern Luzon"
    elif 14.0 <= lat <= 16.0 and 120.0 <= lon <= 121.5:
        return "Central Luzon"
    elif 14.4 <= lat <= 14.8 and 120.9 <= lon <= 121.2:
        return "Metro Manila"
    elif 10.0 <= lat <= 13.0 and 123.0 <= lon <= 126.0:
        return "Visayas Region"
    elif 6.0 <= lat <= 10.0 and 122.0 <= lon <= 126.0:
        return "Mindanao Region"
    elif 13.0 <= lat <= 14.0 and 120.0 <= lon <= 121.0:
        return "Southern Luzon"
    else:
        return "Philippines"

ICON_BASE = "https://openweathermap.org/img/wn/"

def _today():
    return datetime.date.today()

def _fetch_weather_data(lat, lon, days=7):
    """
    Fetch weather data from Open-Meteo API.
    Uses forecast API for current/future data and historical API for past data.
    For periods > 90 days, uses daily aggregation to handle large datasets.
    """
    try:
        # Forecast API for current weather and future forecasts
        forecast_url = (
            f"https://api.open-meteo.com/v1/forecast?"
            f"latitude={lat}&longitude={lon}"
            f"&hourly=temperature_2m,relative_humidity_2m,pressure_msl,"
            f"precipitation,windspeed_10m,winddirection_10m,cloudcover,"
            f"weathercode,visibility"
            f"&daily=temperature_2m_max,temperature_2m_min,weathercode,"
            f"precipitation_sum,windspeed_10m_max"
            f"&current_weather=true"
            f"&timezone=auto"
            f"&forecast_days=16"  # Maximum forecast days
        )
        
        forecast_response = requests.get(forecast_url, timeout=20)
        forecast_response.raise_for_status()
        forecast_data = forecast_response.json()
        
        # Get historical data if days > 0
        historical_data = {"hourly": {}, "daily": {}}
        if days > 0:
            end_date = _today()
            start_date = end_date - datetime.timedelta(days=min(days, 730))  # Support up to 2 years
            
            # For periods > 90 days, use daily data only (more efficient and API-friendly)
            # For periods <= 90 days, use hourly data
            use_hourly = days <= 90
            
            if use_hourly:
                # Use forecast API with past dates for hourly historical data
                historical_url = (
                    f"https://api.open-meteo.com/v1/forecast?"
                    f"latitude={lat}&longitude={lon}"
                    f"&hourly=temperature_2m,relative_humidity_2m,pressure_msl,"
                    f"precipitation,windspeed_10m,cloudcover,weathercode"
                    f"&daily=temperature_2m_max,temperature_2m_min,weathercode"
                    f"&start_date={start_date}&end_date={end_date}"
                    f"&timezone=auto"
                )
            else:
                # For longer periods, use daily data (more efficient)
                historical_url = (
                    f"https://api.open-meteo.com/v1/forecast?"
                    f"latitude={lat}&longitude={lon}"
                    f"&daily=temperature_2m_max,temperature_2m_min,"
                    f"temperature_2m_mean,relative_humidity_2m_max,"
                    f"relative_humidity_2m_min,precipitation_sum,"
                    f"windspeed_10m_max,weathercode"
                    f"&start_date={start_date}&end_date={end_date}"
                    f"&timezone=auto"
                )
            
            try:
                hist_response = requests.get(historical_url, timeout=30)
                if hist_response.status_code == 200:
                    hist_data = hist_response.json()
                    # Check if we got data
                    if use_hourly:
                        if hist_data.get("hourly") and len(hist_data.get("hourly", {}).get("time", [])) > 0:
                            historical_data = hist_data
                    else:
                        if hist_data.get("daily") and len(hist_data.get("daily", {}).get("time", [])) > 0:
                            historical_data = hist_data
                            # Convert daily to hourly-like format for consistency
                            historical_data["hourly"] = _daily_to_hourly_format(hist_data.get("daily", {}))
            except Exception as e:
                # Log error but continue with forecast data only
                print(f"Historical data fetch warning: {str(e)}")
        
        # Merge historical and forecast data
        forecast_hourly = forecast_data.get("hourly", {})
        forecast_daily = forecast_data.get("daily", {})
        hist_hourly = historical_data.get("hourly", {})
        hist_daily = historical_data.get("daily", {})
        
        # Combine hourly data (historical + forecast)
        combined_hourly = {}
        if forecast_hourly and hist_hourly:
            for key in forecast_hourly.keys():
                hist_vals = hist_hourly.get(key, [])
                forecast_vals = forecast_hourly.get(key, [])
                combined_hourly[key] = hist_vals + forecast_vals
        elif forecast_hourly:
            combined_hourly = forecast_hourly
        elif hist_hourly:
            combined_hourly = hist_hourly
        
        # Combine daily data
        combined_daily = {}
        if forecast_daily and hist_daily:
            for key in forecast_daily.keys():
                hist_vals = hist_daily.get(key, [])
                forecast_vals = forecast_daily.get(key, [])
                combined_daily[key] = hist_vals + forecast_vals
        elif forecast_daily:
            combined_daily = forecast_daily
        elif hist_daily:
            combined_daily = hist_daily
        
        return {
            "current": forecast_data.get("current_weather", {}),
            "daily": combined_daily if combined_daily else forecast_daily,
            "hourly": combined_hourly if combined_hourly else forecast_hourly,
            "last_update": datetime.datetime.now().isoformat(),
            "period_days": days,
        }
    except requests.exceptions.RequestException as e:
        raise Exception(f"Weather API error: {str(e)}")

def _daily_to_hourly_format(daily_data):
    """
    Convert daily data to hourly-like format for consistent charting.
    For longer periods, we aggregate daily averages as hourly data points.
    """
    hourly_format = {}
    times = daily_data.get("time", [])
    if not times:
        return {}
    
    # For each daily entry, create 24 hourly entries (using daily values)
    hourly_times = []
    hourly_temps = []
    hourly_humidity = []
    hourly_pressure = []
    hourly_precip = []
    hourly_wind = []
    hourly_cloud = []
    hourly_weathercode = []
    
    for i, date_str in enumerate(times):
        date_obj = datetime.datetime.fromisoformat(date_str.split("T")[0])
        # Create 24 hourly entries for each day using daily averages
        temp_max = daily_data.get("temperature_2m_max", [None])[i] if i < len(daily_data.get("temperature_2m_max", [])) else None
        temp_min = daily_data.get("temperature_2m_min", [None])[i] if i < len(daily_data.get("temperature_2m_min", [])) else None
        temp_mean = daily_data.get("temperature_2m_mean", [None])[i] if i < len(daily_data.get("temperature_2m_mean", [])) else None
        
        # Use mean if available, otherwise average of max/min
        temp_val = temp_mean if temp_mean is not None else ((temp_max + temp_min) / 2 if temp_max is not None and temp_min is not None else temp_max)
        
        for hour in range(24):
            hourly_time = date_obj + datetime.timedelta(hours=hour)
            hourly_times.append(hourly_time.isoformat())
            hourly_temps.append(temp_val)
            hourly_humidity.append(daily_data.get("relative_humidity_2m_max", [None])[i] if i < len(daily_data.get("relative_humidity_2m_max", [])) else None)
            hourly_pressure.append(None)  # Not available in daily format
            hourly_precip.append((daily_data.get("precipitation_sum", [None])[i] or 0) / 24 if i < len(daily_data.get("precipitation_sum", [])) else None)  # Distribute daily precip
            hourly_wind.append(daily_data.get("windspeed_10m_max", [None])[i] if i < len(daily_data.get("windspeed_10m_max", [])) else None)
            hourly_cloud.append(None)  # Not available
            hourly_weathercode.append(daily_data.get("weathercode", [None])[i] if i < len(daily_data.get("weathercode", [])) else None)
    
    hourly_format["time"] = hourly_times
    hourly_format["temperature_2m"] = hourly_temps
    hourly_format["relative_humidity_2m"] = hourly_humidity
    hourly_format["pressure_msl"] = hourly_pressure
    hourly_format["precipitation"] = hourly_precip
    hourly_format["windspeed_10m"] = hourly_wind
    hourly_format["cloudcover"] = hourly_cloud
    hourly_format["weathercode"] = hourly_weathercode
    
    return hourly_format

def _get_hourly_values(payload, dtype):
    h = payload.get("hourly", {})
    return {
        "temperature": h.get("temperature_2m", []),
        "humidity": h.get("relative_humidity_2m", []),
        "pressure": h.get("pressure_msl", []),
        "precipitation": h.get("precipitation", []),
        "windspeed": h.get("windspeed_10m", []),
        "cloudcover": h.get("cloudcover", []),
        "winddirection": h.get("winddirection_10m", []),
        "visibility": h.get("visibility", []),
    }.get(dtype, [])

def _mk_png(fig: Figure) -> str:
    """Return a base64 data URI from a Matplotlib Figure."""
    buf = io.BytesIO()
    fig.savefig(buf, format="png", bbox_inches="tight")
    buf.seek(0)
    b64 = base64.b64encode(buf.read()).decode("utf-8")
    return "data:image/png;base64," + b64

def _trend_chart(payload, dtype):
    xs = payload["hourly"].get("time", [])
    ys = _get_hourly_values(payload, dtype)

    if not xs or not ys:
        return ""

    # Filter out None values and get corresponding indices
    valid_data = [(i, y) for i, y in enumerate(ys) if y is not None]
    if not valid_data:
        return ""

    valid_indices, valid_ys = zip(*valid_data)
    valid_labels = [datetime.datetime.fromisoformat(xs[i]).strftime("%b %d") for i in valid_indices]

    fig = Figure(figsize=(10, 3.5), dpi=150)
    ax = fig.add_subplot(111)

    ax.plot(range(len(valid_ys)), valid_ys, color="#4fc3f7", linewidth=2.2, marker="o", markersize=3)

    ax.set_title(f"{dtype.capitalize()} Trend", fontsize=12, weight="bold", color="#ffffff", pad=18)
    ax.set_ylabel(dtype.capitalize(), fontsize=10, color="#ffffff", labelpad=8)
    ax.set_xlabel("Date", fontsize=10, color="#ffffff", labelpad=10)

    # 🧠 Show fewer, rotated labels to save space
    step = max(1, len(valid_labels) // 8)
    ax.set_xticks(range(0, len(valid_labels), step))
    ax.set_xticklabels(
        [valid_labels[i] for i in range(0, len(valid_labels), step)],
        rotation=40, ha="right", fontsize=8
    )

    # Add min/max/avg info
    ymin, ymax, yavg = min(valid_ys), max(valid_ys), sum(valid_ys) / len(valid_ys)
    ax.text(
        0.02, 0.93,
        f"Min: {ymin:.1f} | Max: {ymax:.1f} | Avg: {yavg:.1f}",
        transform=ax.transAxes,
        fontsize=8, color="#cccccc", va="top"
    )

    # 🎨 Styling
    ax.grid(alpha=0.3, linestyle="--")
    fig.subplots_adjust(left=0.08, right=0.97, top=0.88, bottom=0.35)  # ⬅️ more bottom margin!
    fig.patch.set_alpha(0)
    ax.set_facecolor("none")
    ax.tick_params(colors="#ffffff")

    return _mk_png(fig)


def _distribution_chart(payload, dtype):
    ys = _get_hourly_values(payload, dtype)
    if not ys:
        return ""

    # Filter out None values
    valid_ys = [(i, y) for i, y in enumerate(ys) if y is not None]
    if not valid_ys:
        return ""

    valid_indices, valid_values = zip(*valid_ys)

    fig = Figure(figsize=(10, 3.2), dpi=150)
    ax = fig.add_subplot(111)

    ax.bar(range(len(valid_values)), valid_values, width=0.8, color="#81d4fa", edgecolor="#0288d1")

    ax.set_title(f"{dtype.capitalize()} Distribution", fontsize=12, weight="bold", color="#ffffff", pad=18)
    ax.set_ylabel(dtype.capitalize(), fontsize=10, color="#ffffff", labelpad=10)
    ax.grid(axis="y", alpha=0.3, linestyle="--")

    # 🔧 give enough space below
    fig.subplots_adjust(left=0.08, right=0.97, top=0.88, bottom=0.28)
    fig.patch.set_alpha(0)
    ax.set_facecolor("none")
    ax.tick_params(colors="#ffffff")

    return _mk_png(fig)

def _stats(payload, dtype):
    ys = _get_hourly_values(payload, dtype)
    if not ys:
        return {"min": None, "max": None, "avg": None}
    # Filter out None values
    valid_ys = [y for y in ys if y is not None]
    if not valid_ys:
        return {"min": None, "max": None, "avg": None}
    return {
        "min": round(min(valid_ys), 1),
        "max": round(max(valid_ys), 1),
        "avg": round(sum(valid_ys) / len(valid_ys), 1),
    }

def _calculate_advanced_stats(payload, dtype):
    """Calculate advanced statistics including trends, percentiles, and variability."""
    ys = _get_hourly_values(payload, dtype)
    if not ys:
        return {}
    
    # Filter out None values
    valid_ys = [y for y in ys if y is not None]
    if not valid_ys or len(valid_ys) < 2:
        return {}
    
    sorted_ys = sorted(valid_ys)
    n = len(valid_ys)
    
    # Basic stats
    avg = sum(valid_ys) / n
    variance = sum((x - avg) ** 2 for x in valid_ys) / n
    std_dev = math.sqrt(variance)
    
    # Percentiles
    percentile_25 = sorted_ys[int(n * 0.25)] if n > 0 else None
    percentile_75 = sorted_ys[int(n * 0.75)] if n > 0 else None
    median = sorted_ys[int(n * 0.5)] if n > 0 else None
    
    # Trend analysis (simple linear regression slope)
    trend_slope = 0
    if n >= 2:
        x_mean = (n - 1) / 2
        y_mean = avg
        numerator = sum((i - x_mean) * (valid_ys[i] - y_mean) for i in range(n))
        denominator = sum((i - x_mean) ** 2 for i in range(n))
        if denominator != 0:
            trend_slope = numerator / denominator
    
    # Variability coefficient
    cv = (std_dev / avg * 100) if avg != 0 else 0
    
    return {
        "median": round(median, 1) if median is not None else None,
        "std_dev": round(std_dev, 2),
        "variance": round(variance, 2),
        "percentile_25": round(percentile_25, 1) if percentile_25 is not None else None,
        "percentile_75": round(percentile_75, 1) if percentile_75 is not None else None,
        "trend_slope": round(trend_slope, 4),
        "trend_direction": "increasing" if trend_slope > 0.01 else ("decreasing" if trend_slope < -0.01 else "stable"),
        "coefficient_of_variation": round(cv, 2),
    }

def _is_daytime(now=None):
    now = now or datetime.datetime.now()
    return 6 <= now.hour < 18

def _icon_from_wmo(code, day=True):
    mapping = {
        0: "01", 1: "02", 2: "03", 3: "04",
        45: "50", 48: "50",
        51: "09", 53: "09", 55: "09",
        56: "13", 57: "13",
        61: "10", 63: "10", 65: "09",
        66: "13", 67: "13",
        71: "13", 73: "13", 75: "13",
        77: "13", 80: "09", 81: "10", 82: "11",
        85: "13", 86: "13", 95: "11", 96: "11", 99: "11",
    }
    base = mapping.get(int(code or 2), "03")
    return f"{base}{'d' if day else 'n'}"

def _desc_from_wmo(code):
    return {
        0:"Clear ☀️",1:"Mainly clear 🌤",2:"Partly cloudy ⛅",3:"Cloudy ☁️",
        45:"Fog 🌫",48:"Rime fog 🌫",51:"Light drizzle 🌦",61:"Rain 🌧",
        71:"Snow ❄️",95:"Thunderstorm ⛈"
    }.get(int(code or 2), "Partly cloudy 🌤")

def _generate_alerts(current, hourly_data):
    """Generate weather alerts based on current conditions and trends."""
    alerts = []
    
    temp = current.get("temperature")
    wind = current.get("windspeed")
    wmo = current.get("weathercode", 2)
    
    # Get latest values from hourly data
    temps = hourly_data.get("temperature_2m", [])
    humidities = hourly_data.get("relative_humidity_2m", [])
    pressures = hourly_data.get("pressure_msl", [])
    precipitations = hourly_data.get("precipitation", [])
    
    # Temperature alerts
    if temp is not None:
        if temp >= 38:
            alerts.append({
                "type": "extreme_heat",
                "severity": "high",
                "message": f"⚠️ Extreme Heat Warning: {temp}°C - Stay hydrated and avoid prolonged outdoor activities"
            })
        elif temp <= 10:
            alerts.append({
                "type": "cold",
                "severity": "medium",
                "message": f"🧊 Cold Weather Alert: {temp}°C - Dress warmly"
            })
    
    # Wind alerts
    if wind is not None:
        if wind >= 62:  # Tropical storm force
            alerts.append({
                "type": "wind",
                "severity": "high",
                "message": f"💨 Strong Wind Warning: {wind} km/h - Dangerous conditions"
            })
        elif wind >= 39:  # Strong breeze
            alerts.append({
                "type": "wind",
                "severity": "medium",
                "message": f"💨 Strong Wind: {wind} km/h - Exercise caution"
            })
    
    # Precipitation alerts
    if precipitations and len(precipitations) > 0:
        # Filter out None values and convert to float, defaulting to 0
        recent_data = precipitations[-24:] if len(precipitations) >= 24 else precipitations
        recent_precip = sum((float(x) if x is not None else 0) for x in recent_data)
        if recent_precip >= 50:  # Heavy rain (24h)
            alerts.append({
                "type": "rain",
                "severity": "high",
                "message": f"🌧️ Heavy Rainfall Alert: {recent_precip:.1f}mm in last 24h - Risk of flooding"
            })
        elif recent_precip >= 25:
            alerts.append({
                "type": "rain",
                "severity": "medium",
                "message": f"🌧️ Moderate Rainfall: {recent_precip:.1f}mm in last 24h"
            })
    
    # Weather code alerts
    if wmo in [95, 96, 99]:  # Thunderstorm
        alerts.append({
            "type": "thunderstorm",
            "severity": "high",
            "message": "⛈️ Thunderstorm Warning - Seek shelter immediately"
        })
    elif wmo in [71, 73, 75, 77, 85, 86]:  # Snow
        alerts.append({
            "type": "snow",
            "severity": "medium",
            "message": "❄️ Snow Alert - Drive carefully"
        })
    elif wmo in [45, 48]:  # Fog
        alerts.append({
            "type": "fog",
            "severity": "medium",
            "message": "🌫️ Dense Fog - Low visibility conditions"
        })
    
    # Humidity alerts
    if humidities and len(humidities) > 0:
        recent_humidities = humidities[-12:] if len(humidities) >= 12 else humidities
        valid_humidities = [h for h in recent_humidities if h is not None]
        if valid_humidities:
            avg_humidity = sum(valid_humidities) / len(valid_humidities)
            if avg_humidity >= 90:
                alerts.append({
                    "type": "humidity",
                    "severity": "low",
                    "message": f"💧 High Humidity: {avg_humidity:.0f}% - Very humid conditions"
                })
    
    # Pressure alerts (rapid changes indicate weather changes)
    if pressures and len(pressures) >= 12:
        recent_pressure = pressures[-1]
        past_pressure = pressures[-12] if len(pressures) >= 12 else pressures[0]
        # Check that both values are not None
        if recent_pressure is not None and past_pressure is not None:
            pressure_change = recent_pressure - past_pressure
            if abs(pressure_change) >= 5:  # Significant pressure change
                trend = "dropping rapidly" if pressure_change < 0 else "rising rapidly"
                alerts.append({
                    "type": "pressure",
                    "severity": "medium",
                    "message": f"🔵 Pressure {trend}: {pressure_change:+.1f} hPa - Weather conditions changing"
                })
    
    return alerts

# -----------------------------
# Page render helpers
# -----------------------------
def _compose_context(args):
    # controls
    city_key = args.get("city", "manila")
    days_str = args.get("days", "7")
    try:
        days = int(days_str)
        # Limit to maximum 30 days
        if days > 30:
            days = 30
        elif days < 1:
            days = 1
    except (ValueError, TypeError):
        days = 7  # Default to 7 days if invalid
    dtype = args.get("type", "temperature")

    # coordinates: custom (lat/lon) wins, preserve from URL if exists
    lat = args.get("lat")
    lon = args.get("lon")
    name = args.get("place")  # ✅ FIXED: use place name sent from JS

    # Philippines bounds validation
    PH_MIN_LAT = 4.2
    PH_MAX_LAT = 21.1
    PH_MIN_LON = 116.9
    PH_MAX_LON = 127.0

    def validate_philippines_coords(lat_val, lon_val):
        """Validate coordinates are within Philippines boundaries."""
        if lat_val < PH_MIN_LAT or lat_val > PH_MAX_LAT:
            return False
        if lon_val < PH_MIN_LON or lon_val > PH_MAX_LON:
            return False
        return True

    def constrain_to_philippines(lat_val, lon_val):
        """Constrain coordinates to Philippines bounds."""
        lat_val = max(PH_MIN_LAT, min(PH_MAX_LAT, lat_val))
        lon_val = max(PH_MIN_LON, min(PH_MAX_LON, lon_val))
        return lat_val, lon_val

    if lat and lon:
        lat, lon = float(lat), float(lon)
        # Validate and constrain to Philippines
        if not validate_philippines_coords(lat, lon):
            lat, lon = constrain_to_philippines(lat, lon)
            name = (name or "Constrained Location") + " (PH)"
        name = name or "Custom Location"
    else:
        city = CITIES.get(city_key, CITIES["manila"])
        lat, lon = city["lat"], city["lon"]
        name = city["label"]


    try:
        payload = _fetch_weather_data(lat, lon, days)
    except Exception as e:
        return {
            "error": str(e),
            "cities": CITIES,
            "selected": {"city": city_key, "days": days, "type": dtype},
            "place_name": name or "Unknown",
            "coords": {"lat": lat, "lon": lon},
        }

    # current widget
    hourly = payload.get("hourly", {})
    current = payload.get("current", {})
    
    # Get current values - try current_weather first, then latest hourly
    current_temp = current.get("temperature")
    if current_temp is None and hourly.get("temperature_2m"):
        current_temp = hourly["temperature_2m"][-1] if hourly["temperature_2m"] else None
    
    current_wind = current.get("windspeed")
    if current_wind is None and hourly.get("windspeed_10m"):
        current_wind = hourly["windspeed_10m"][-1] if hourly["windspeed_10m"] else None
    
    # Get humidity - try latest hourly value, filtering out None
    current_humidity = None
    if hourly.get("relative_humidity_2m") and len(hourly.get("relative_humidity_2m", [])) > 0:
        hourly_humidity = hourly["relative_humidity_2m"]
        valid_humidity = [h for h in hourly_humidity if h is not None]
        if valid_humidity:
            current_humidity = valid_humidity[-1]
    
    # Get pressure - try latest hourly value, filtering out None
    current_pressure = None
    if hourly.get("pressure_msl") and len(hourly.get("pressure_msl", [])) > 0:
        hourly_pressure = hourly["pressure_msl"]
        valid_pressure = [p for p in hourly_pressure if p is not None]
        if valid_pressure:
            current_pressure = valid_pressure[-1]
    
    # If still no pressure/humidity, try to fetch from current forecast API
    if current_pressure is None or current_humidity is None:
        try:
            forecast_url = (
                f"https://api.open-meteo.com/v1/forecast?"
                f"latitude={lat}&longitude={lon}"
                f"&current=temperature_2m,relative_humidity_2m,pressure_msl,weathercode"
                f"&timezone=auto"
            )
            forecast_response = requests.get(forecast_url, timeout=10)
            if forecast_response.status_code == 200:
                forecast_data = forecast_response.json()
                current_data = forecast_data.get("current", {})
                if current_humidity is None and current_data.get("relative_humidity_2m") is not None:
                    current_humidity = current_data.get("relative_humidity_2m")
                if current_pressure is None and current_data.get("pressure_msl") is not None:
                    current_pressure = current_data.get("pressure_msl")
        except Exception as e:
            print(f"Warning: Could not fetch current pressure/humidity: {e}")
    
    current_display = {
        "temperature": round(current_temp, 1) if current_temp is not None else None,
        "windspeed": round(current_wind, 1) if current_wind is not None else None,
        "winddirection": current.get("winddirection") or (hourly.get("winddirection_10m") or [None])[-1],
        "pressure": round(current_pressure, 1) if current_pressure is not None else None,
        "humidity": round(current_humidity, 1) if current_humidity is not None else None,
        "cloudcover": round((hourly.get("cloudcover") or [None])[-1], 1) if (hourly.get("cloudcover") or [None])[-1] is not None else None,
        "precipitation": round((hourly.get("precipitation") or [None])[-1], 2) if (hourly.get("precipitation") or [None])[-1] is not None else None,
        "visibility": round((hourly.get("visibility") or [None])[-1] / 1000, 2) if (hourly.get("visibility") or [None])[-1] is not None else None,
        "wmo": current.get("weathercode", 2),
        "last_update": payload.get("last_update"),
    }

    icon = ICON_BASE + _icon_from_wmo(current_display["wmo"], day=_is_daytime()) + "@2x.png"
    description = _desc_from_wmo(current_display["wmo"])

    # Generate weather alerts
    alerts = _generate_alerts(current, hourly)
    
    # charts
    trend_png = _trend_chart(payload, dtype)
    distribution_png = _distribution_chart(payload, dtype)
    stats = _stats(payload, dtype)
    
    # Advanced statistics
    advanced_stats = _calculate_advanced_stats(payload, dtype)

    # forecast cards (limit by selected days)
    daily = payload.get("daily", {})
    limit = min(days, len(daily.get("time", [])))
    forecast = []
    today = datetime.datetime.now().date()
    for i in range(limit):
        t = daily["time"][i]
        date_obj = datetime.datetime.fromisoformat(t).date()
        # Show "Today" for today, date for tomorrow, day name for future days
        if date_obj == today:
            label = "Today"
        elif date_obj == today + datetime.timedelta(days=1):
            label = datetime.datetime.fromisoformat(t).strftime("%b %d")
        else:
            label = datetime.datetime.fromisoformat(t).strftime("%a, %b %d")
        wmo = (daily.get("weathercode") or [2])[i]
        icon_day = ICON_BASE + _icon_from_wmo(wmo, day=_is_daytime()) + "@2x.png"
        forecast.append({
            "label": label,
            "max": daily.get("temperature_2m_max", [None])[i],
            "min": daily.get("temperature_2m_min", [None])[i],
            "icon": icon_day
        })

    # table
    table_rows = []
    times = hourly.get("time", [])
    vals = _get_hourly_values(payload, dtype)
    for i, t in enumerate(times):
        ts = datetime.datetime.fromisoformat(t).strftime("%m/%d/%Y, %I:%M:%S %p")
        v = vals[i] if i < len(vals) else None
        table_rows.append((ts, v))

    # mini-forecast for sidebar (always show 7 days)
    daily_times = daily.get("time", [])
    sidebar_forecast = []
    today = datetime.datetime.now().date()
    
    # Always show exactly 7 days (or as many as available)
    sidebar_days = min(7, len(daily_times))
    
    for i in range(sidebar_days):
        t = daily_times[i]
        date_obj = datetime.datetime.fromisoformat(t).date()
        # Show "Today" for today, date for tomorrow, day name for future days
        if date_obj == today:
            lab = "Today"
        elif date_obj == today + datetime.timedelta(days=1):
            lab = datetime.datetime.fromisoformat(t).strftime("%b %d")
        else:
            lab = datetime.datetime.fromisoformat(t).strftime("%a, %b %d")
        wmo = (daily.get("weathercode") or [2])[i] if i < len(daily.get("weathercode", [])) else 2
        mini_icon = ICON_BASE + _icon_from_wmo(wmo, day=_is_daytime()) + ".png"
        sidebar_forecast.append({
            "label": lab,
            "icon": mini_icon,
            "max": daily.get("temperature_2m_max", [None])[i] if i < len(daily.get("temperature_2m_max", [])) else None,
            "min": daily.get("temperature_2m_min", [None])[i] if i < len(daily.get("temperature_2m_min", [])) else None,
        })

    # Get region information
    region_info = _get_region_info(lat, lon, name)
    coords_display = f"{lat:.4f}°N, {lon:.4f}°E"
    
    context = {
        "cities": CITIES,
        "selected": {"city": city_key, "days": days, "type": dtype},
        "place_name": name,
        "coords": {"lat": lat, "lon": lon},
        "region_info": region_info,
        "coords_display": coords_display,
        "payload": payload,
        "current": current_display,
        "headline": f"Weather for {name} — {description}",
        "current_icon": icon,
        "trend_png": trend_png,
        "distribution_png": distribution_png,
        "stats": stats,
        "advanced_stats": advanced_stats,
        "alerts": alerts,
        "forecast": forecast,
        "table_rows": table_rows,
        "sidebar_forecast": sidebar_forecast,
        "last_update": current_display.get("last_update"),
    }
    return context

# -----------------------------
# Routes
# -----------------------------
@app.route("/")
def index():
    ctx = _compose_context(request.args)
    return render_template("index.html", **ctx)

# HTMX target: re-render the whole shell (sidebar + main) so the sidebar updates too
@app.route("/partial/page")
def partial_page():
    ctx = _compose_context(request.args)
    return render_template("_page_shell.html", **ctx)

# Real-time update endpoint (JSON)
@app.route("/api/current")
def api_current():
    """Return current weather data as JSON for real-time updates."""
    args = request.args
    city = args.get("city")
    lat = args.get("lat")
    lon = args.get("lon")
    
    if lat and lon:
        lat, lon = float(lat), float(lon)
        name = args.get("place", "Custom Location")
    else:
        c = CITIES.get(city or "manila", CITIES["manila"])
        lat, lon = c["lat"], c["lon"]
        name = c["label"]
    
    # Philippines bounds validation for API endpoint
    PH_MIN_LAT = 4.2
    PH_MAX_LAT = 21.1
    PH_MIN_LON = 116.9
    PH_MAX_LON = 127.0

    def validate_philippines_coords_api(lat_val, lon_val):
        """Validate coordinates are within Philippines boundaries."""
        if lat_val < PH_MIN_LAT or lat_val > PH_MAX_LAT:
            return False
        if lon_val < PH_MIN_LON or lon_val > PH_MAX_LON:
            return False
        return True

    try:
        # Validate coordinates before fetching weather data
        if lat and lon:
            lat, lon = float(lat), float(lon)
            if not validate_philippines_coords_api(lat, lon):
                return jsonify({
                    "success": False,
                    "error": "Location must be within Philippines boundaries",
                }), 400
        
        payload = _fetch_weather_data(lat, lon, days=0)  # Just current/future
        hourly = payload.get("hourly", {})
        current = payload.get("current", {})
        
        current_temp = current.get("temperature")
        if current_temp is None and hourly.get("temperature_2m"):
            current_temp = hourly["temperature_2m"][-1] if hourly["temperature_2m"] else None
        
        current_wind = current.get("windspeed")
        if current_wind is None and hourly.get("windspeed_10m"):
            current_wind = hourly["windspeed_10m"][-1] if hourly["windspeed_10m"] else None
        
        # Get humidity - try latest hourly value, filtering out None
        current_humidity = None
        if hourly.get("relative_humidity_2m") and len(hourly.get("relative_humidity_2m", [])) > 0:
            hourly_humidity = hourly["relative_humidity_2m"]
            valid_humidity = [h for h in hourly_humidity if h is not None]
            if valid_humidity:
                current_humidity = valid_humidity[-1]
        
        # Get pressure - try latest hourly value, filtering out None
        current_pressure = None
        if hourly.get("pressure_msl") and len(hourly.get("pressure_msl", [])) > 0:
            hourly_pressure = hourly["pressure_msl"]
            valid_pressure = [p for p in hourly_pressure if p is not None]
            if valid_pressure:
                current_pressure = valid_pressure[-1]
        
        # If still no pressure/humidity, try to fetch from current forecast API
        if current_pressure is None or current_humidity is None:
            try:
                forecast_url = (
                    f"https://api.open-meteo.com/v1/forecast?"
                    f"latitude={lat}&longitude={lon}"
                    f"&current=temperature_2m,relative_humidity_2m,pressure_msl,weathercode"
                    f"&timezone=auto"
                )
                forecast_response = requests.get(forecast_url, timeout=10)
                if forecast_response.status_code == 200:
                    forecast_data = forecast_response.json()
                    current_data = forecast_data.get("current", {})
                    if current_humidity is None and current_data.get("relative_humidity_2m") is not None:
                        current_humidity = current_data.get("relative_humidity_2m")
                    if current_pressure is None and current_data.get("pressure_msl") is not None:
                        current_pressure = current_data.get("pressure_msl")
            except Exception as e:
                print(f"Warning: Could not fetch current pressure/humidity: {e}")
        
        return jsonify({
            "success": True,
            "location": name,
            "coordinates": {"lat": lat, "lon": lon},
            "current": {
                "temperature": round(current_temp, 1) if current_temp is not None else None,
                "windspeed": round(current_wind, 1) if current_wind is not None else None,
                "winddirection": current.get("winddirection"),
                "pressure": round(current_pressure, 1) if current_pressure is not None else None,
                "humidity": round(current_humidity, 1) if current_humidity is not None else None,
                "cloudcover": round((hourly.get("cloudcover") or [None])[-1], 1) if (hourly.get("cloudcover") or [None])[-1] is not None else None,
                "precipitation": round((hourly.get("precipitation") or [None])[-1], 2) if (hourly.get("precipitation") or [None])[-1] is not None else None,
                "weathercode": current.get("weathercode", 2),
            },
            "alerts": _generate_alerts(current, hourly),
            "last_update": payload.get("last_update"),
            "timestamp": datetime.datetime.now().isoformat(),
        })
    except Exception as e:
        return jsonify({
            "success": False,
            "error": str(e),
        }), 500

# JSON API for full weather data
@app.route("/api/weather")
def api_weather():
    """Return full weather data as JSON."""
    args = request.args
    city = args.get("city")
    lat = args.get("lat")
    lon = args.get("lon")
    days = int(args.get("days", "7"))
    
    if lat and lon:
        lat, lon = float(lat), float(lon)
    else:
        c = CITIES.get(city or "manila", CITIES["manila"])
        lat, lon = c["lat"], c["lon"]
    
    try:
        data = _fetch_weather_data(lat, lon, days)
        return jsonify(data)
    except Exception as e:
        return jsonify({"error": str(e)}), 500

# Export data endpoint
@app.route("/api/export")
def api_export():
    """Export weather data as CSV or JSON."""
    args = request.args
    city = args.get("city")
    lat = args.get("lat")
    lon = args.get("lon")
    days = int(args.get("days", "7"))
    format_type = args.get("format", "json")  # json or csv
    
    if lat and lon:
        lat, lon = float(lat), float(lon)
        name = args.get("place", "Custom Location")
    else:
        c = CITIES.get(city or "manila", CITIES["manila"])
        lat, lon = c["lat"], c["lon"]
        name = c["label"]
    
    try:
        payload = _fetch_weather_data(lat, lon, days)
        hourly = payload.get("hourly", {})
        
        if format_type == "csv":
            import csv
            output = io.StringIO()
            writer = csv.writer(output)
            
            # Header
            times = hourly.get("time", [])
            writer.writerow(["Time", "Temperature", "Humidity", "Pressure", "Precipitation", "Windspeed", "Cloudcover"])
            
            # Data rows
            for i, time in enumerate(times):
                writer.writerow([
                    time,
                    hourly.get("temperature_2m", [None])[i] if i < len(hourly.get("temperature_2m", [])) else None,
                    hourly.get("relative_humidity_2m", [None])[i] if i < len(hourly.get("relative_humidity_2m", [])) else None,
                    hourly.get("pressure_msl", [None])[i] if i < len(hourly.get("pressure_msl", [])) else None,
                    hourly.get("precipitation", [None])[i] if i < len(hourly.get("precipitation", [])) else None,
                    hourly.get("windspeed_10m", [None])[i] if i < len(hourly.get("windspeed_10m", [])) else None,
                    hourly.get("cloudcover", [None])[i] if i < len(hourly.get("cloudcover", [])) else None,
                ])
            
            from flask import Response
            return Response(
                output.getvalue(),
                mimetype="text/csv",
                headers={"Content-Disposition": f"attachment; filename=weather_data_{name}_{datetime.date.today()}.csv"}
            )
        else:
            return jsonify({
                "location": name,
                "coordinates": {"lat": lat, "lon": lon},
                "period_days": days,
                "data": payload,
                "exported_at": datetime.datetime.now().isoformat(),
            })
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == "__main__":
    app.run(debug=True, port=5001, host='127.0.0.1')
