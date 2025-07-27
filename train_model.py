import pandas as pd
from sklearn.linear_model import LinearRegression
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import LabelEncoder
from sklearn.metrics import mean_squared_error
import joblib

# Load dataset
df = pd.read_csv('dataset/dataset.csv')

# Clean and preprocess
df = df[df['Price_Per_Liter (USD)'] > 0]  # Remove rows with zero price
features = [
    'Price_Per_Barrel (USD) ', 'Fuel_Type', 'Import_Volume (Barrels)',
    'Transportation_Cost (USD)', 'Taxes (%)', 'Storage_Cost (USD)',
    'Exchange_Rate_Fluctuations (%)', 'Seasonal_Demand_Variations (Scale)'
]

# Encode Fuel_Type
le = LabelEncoder()
df['Fuel_Type'] = le.fit_transform(df['Fuel_Type'])

X = df[features]
y = df['Price_Per_Liter (USD)']

# Train/test split
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# Train model
model = LinearRegression()
model.fit(X_train, y_train)

# Evaluate
y_pred = model.predict(X_test)
mse = mean_squared_error(y_test, y_pred)
print(f'Model trained. Test MSE: {mse:.4f}')

# Save model and label encoder
joblib.dump({'model': model, 'label_encoder': le}, 'model.pkl')
print('Model and encoder saved as model.pkl') 