import sys
import joblib
import numpy as np

# Usage: python predict.py <Price_Per_Barrel> <Fuel_Type> <Import_Volume> <Transportation_Cost> <Taxes> <Storage_Cost> <Exchange_Rate_Fluctuations> <Seasonal_Demand_Variations>
if len(sys.argv) != 9:
    print("Usage: python predict.py <Price_Per_Barrel> <Fuel_Type> <Import_Volume> <Transportation_Cost> <Taxes> <Storage_Cost> <Exchange_Rate_Fluctuations> <Seasonal_Demand_Variations>")
    sys.exit(1)

# Load model and encoder
bundle = joblib.load('model.pkl')
model = bundle['model']
le = bundle['label_encoder']

# Parse arguments
price_per_barrel = float(sys.argv[1])
fuel_type = sys.argv[2]
import_volume = float(sys.argv[3])
transportation_cost = float(sys.argv[4])
taxes = float(sys.argv[5])
storage_cost = float(sys.argv[6])
exchange_rate_fluctuations = float(sys.argv[7])
seasonal_demand_variations = float(sys.argv[8])

# Encode fuel type
fuel_type_encoded = le.transform([fuel_type])[0]

# Prepare input
X = np.array([[price_per_barrel, fuel_type_encoded, import_volume, transportation_cost, taxes, storage_cost, exchange_rate_fluctuations, seasonal_demand_variations]])

# Predict
pred = model.predict(X)[0]
print(f"{pred:.6f}") 