
import numpy as np
from scipy.optimize import curve_fit
import warnings
import sys
import json

# הגדרת מודלים אפשריים
def constant(x, a): return np.full_like(x, a)
def linear(x, a, b): return a * x + b
def quadratic(x, a, b, c): return a * x**2 + b * x + c
def cubic(x, a, b, c, d): return a * x**3 + b * x**2 + c * x + d
def logarithmic(x, a, b): return a * np.log(x) + b
def exponential(x, a, b): return a * np.exp(b * x)

# הפונקציה הראשית שמנתחת טסטים
def analyze_tests(x_vals, y_vals):
    warnings.filterwarnings("ignore")
    x = np.array(x_vals, dtype=float)
    y = np.array(y_vals, dtype=float)

    models = [
        (constant, "O(1)"),
        (linear, "O(n)"),
        (quadratic, "O(n^2)"),
        (cubic, "O(n^3)"),
        (logarithmic, "O(log n)"),
        (exponential, "O(2^n)")
    ]

    best_result = {
        "AIC": float('inf'),
        "MSE": None,
        "Complexity": None
    }
    
    for model_func, complexity in models:
        try:
            popt, _ = curve_fit(model_func, x, y, maxfev=10000)
            y_pred = model_func(x, *popt)
            residuals = y - y_pred
            rss = np.sum(residuals**2)
            mse = np.mean(residuals**2)
            n = len(y)
            k = len(popt)
            aic = n * np.log(rss / n) + 2 * k

            if aic < best_result["AIC"]:
                best_result = {
                    "MSE": mse,
                    "Complexity": complexity,
                    "AIC": aic
                }
        except:
            continue

    # הדפסת התוצאה הסופית
    print(f"Best model: {best_result['Complexity']}")
    print(f"MSE: {best_result['MSE']}")

    return best_result["MSE"]


if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python script.py <array1> <array2>")
        sys.exit(1)

    x_vals = json.loads(sys.argv[1])  
    y_vals = json.loads(sys.argv[2])  

    analyze_tests(x_vals, y_vals)
    
    

