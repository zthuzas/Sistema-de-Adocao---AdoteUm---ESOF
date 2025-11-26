from flask import Flask, request, jsonify, render_template
import pandas as pd
import traceback
import math
import os

app = Flask(__name__)

# --- CONFIGURAÇÃO DE LIMITES (Escala Rúpias - Valores Altos) ---
# Aumentei os MAX para comportar os valores originais do CSV sem estourar a normalização
STATS = {
    'selling_price':     {'min': 5000.0,    'max': 1000000.0}, 
    'year':              {'min': 2000.0,    'max': 2025.0}, 
    'km_driven':         {'min': 1000.0,    'max': 100000.0},
    'ex_showroom_price': {'min': 50000.0,   'max': 1000000.0}
}

# --- CARREGAMENTO DE DADOS ---
# Tentativa de carga do PMML (Apenas para não gerar erro se o arquivo existir)
try:
    from pypmml import Model
except:
    pass

df_motos = pd.DataFrame()
colunas_csv_ok = False

try:
    if os.path.exists('BIKE DETAILS.csv'):
        df_motos = pd.read_csv('BIKE DETAILS.csv')
        df_motos.columns = [c.strip() for c in df_motos.columns]
        # Remove linhas com dados vazios cruciais
        df_motos = df_motos.dropna(subset=['selling_price', 'year', 'km_driven'])
        
        colunas_necessarias = ['selling_price', 'year', 'km_driven', 'name']
        if all(col in df_motos.columns for col in colunas_necessarias):
            colunas_csv_ok = True
            print(f"✅ Base Carregada: {len(df_motos)} veículos para busca vetorial.")
    else:
        print("❌ CSV não encontrado.")
except:
    print("❌ Erro ao ler CSV.")

# --- LÓGICA AUXILIAR ---
def normalizar(valor, coluna):
    try:
        val = float(valor)
        min_val = STATS[coluna]['min']
        max_val = STATS[coluna]['max']
        if max_val == min_val: return 0.0
        norm = (val - min_val) / (max_val - min_val)
        return max(0.0, min(1.0, norm)) 
    except:
        return 0.0

# Centróides (Usados APENAS para dar o Rótulo "Premium" ou "Transacional")
CENTROIDES = {
    0: {'selling_price': 0.15, 'year': 0.90, 'km_driven': 0.15}, # Premium
    1: {'selling_price': 0.03, 'year': 0.60, 'km_driven': 0.50}  # Transacional
}

# Pesos para a Busca de Similaridade
# Define o que é mais importante na hora de achar a moto "gêmea"
PESOS = {
    'selling_price': 4.0, # Preço é o fator mais forte
    'year': 2.0,          # Ano importa bastante
    'km_driven': 1.0,     # KM importa menos
}

def calcular_distancia_simples(dados_norm, centro_norm):
    soma = 0
    for col in ['selling_price', 'year', 'km_driven']:
        if col in dados_norm and col in centro_norm:
            soma += (dados_norm[col] - centro_norm[col]) ** 2
    return math.sqrt(soma)

# --- ROTAS ---
@app.route('/')
def home():
    return render_template('index.html')

@app.route('/predict', methods=['POST'])
def predict():
    try:
        data = request.json
        
        # --- PASSO 1: AJUSTE DE ESCALA (Input do Usuário) ---
        # O usuário digita em Reais (ex: 40.000). O CSV está em Rúpias (~400.000).
        # Multiplicamos por 10 AQUI para a matemática de busca funcionar.
        input_price_ajustado = float(data.get('price', 0)) * 10
        input_showroom_ajustado = float(data.get('showroom', 0)) * 10
        
        # Normaliza a entrada ajustada
        input_norm = {   
            'selling_price':     normalizar(input_price_ajustado, 'selling_price'),
            'year':              normalizar(data.get('year', 0), 'year'),
            'km_driven':         normalizar(data.get('km', 0), 'km_driven'),
            'ex_showroom_price': normalizar(input_showroom_ajustado, 'ex_showroom_price')
        }

        # --- PASSO 2: DEFINIÇÃO DO RÓTULO (Premium vs Transacional) ---
        d0 = calcular_distancia_simples(input_norm, CENTROIDES[0])
        d1 = calcular_distancia_simples(input_norm, CENTROIDES[1])
        cluster_id = 0 if d0 < d1 else 1

        if cluster_id == 0: 
            perfil = "Alto Valor & Tecnologia (Premium)"
            tipo = "premium"
        else:
            perfil = "Custo-Benefício (Transacional)"
            tipo = "transacional"

        # --- PASSO 3: BUSCA VETORIAL k-NN (A Lógica de Proximidade) ---
        motos_lista = []
        
        if colunas_csv_ok and not df_motos.empty:
            # Trabalhamos numa cópia para não alterar a base original
            df_calc = df_motos.copy()
            
            # A. Normalizamos toda a base de dados (Vetorização rápida)
            norm_price = (df_calc['selling_price'] - STATS['selling_price']['min']) / (STATS['selling_price']['max'] - STATS['selling_price']['min'])
            norm_year  = (df_calc['year'] - STATS['year']['min']) / (STATS['year']['max'] - STATS['year']['min'])
            norm_km    = (df_calc['km_driven'] - STATS['km_driven']['min']) / (STATS['km_driven']['max'] - STATS['km_driven']['min'])
            
            # B. Calculamos a distância de CADA moto para a entrada do usuário
            # Fórmula: Diferença ao quadrado * Peso
            df_calc['distancia_knn'] = (
                ((norm_price - input_norm['selling_price']) ** 2 * PESOS['selling_price']) +
                ((norm_year  - input_norm['year'])          ** 2 * PESOS['year']) +
                ((norm_km    - input_norm['km_driven'])     ** 2 * PESOS['km_driven'])
            )
            
            # C. Ordenamos pela MENOR distância (Ascendente) e pegamos as 3 primeiras
            rec = df_calc.sort_values('distancia_knn', ascending=True).head(3)
            
            # D. Formatamos para dicionário
            cols_to_dict = ['name', 'selling_price', 'year', 'km_driven']
            motos_lista = rec[cols_to_dict].to_dict('records')

            # --- PASSO 4: CORREÇÃO DE SAÍDA ---
            # Dividimos o preço encontrado por 10 para mostrar em Reais
            for moto in motos_lista:
                moto['selling_price'] = moto['selling_price'] / 10

        mensagem_base = f"Veículos matematicamente mais próximos do seu perfil."

        return jsonify({
            'perfil': perfil,
            'tipo': tipo,
            'motos': motos_lista,
            'mensagem_base': mensagem_base,
            'debug_origem': ""
        })

    except Exception as e:
        traceback.print_exc() 
        return jsonify({'erro': str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True)