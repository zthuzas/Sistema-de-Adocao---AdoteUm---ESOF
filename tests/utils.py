import os
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.chrome.options import Options

# --- CONFIGURAÇÕES GERAIS ---
BASE_URL = "http://localhost/SistemaAd0911" 

# --- CORREÇÃO DE CAMINHO ---
# Pega o caminho absoluto da pasta onde ESTE arquivo (utils.py) está
DIRETORIO_ATUAL = os.path.dirname(os.path.abspath(__file__))

# Monta o caminho completo para o driver
CAMINHO_DRIVER = os.path.join(DIRETORIO_ATUAL, "chromedriver.exe")

PASTA_PRINTS = os.path.join(DIRETORIO_ATUAL, "evidencias")

if not os.path.exists(PASTA_PRINTS):
    os.makedirs(PASTA_PRINTS)

def iniciar_driver():
    """Configura e abre o navegador Chrome."""
    
    # Debug: Mostra onde ele está procurando (útil para você ver no terminal)
    print(f" > Procurando driver em: {CAMINHO_DRIVER}")

    if not os.path.exists(CAMINHO_DRIVER):
        print(f"ERRO CRÍTICO: O arquivo não foi encontrado no caminho acima.")
        print(f"Certifique-se que 'chromedriver.exe' está na pasta: {DIRETORIO_ATUAL}")
        exit()

    chrome_options = Options()
    chrome_options.add_argument("--log-level=3") 
    chrome_options.add_experimental_option('excludeSwitches', ['enable-logging'])
    
    service = Service(executable_path=CAMINHO_DRIVER)
    navegador = webdriver.Chrome(service=service, options=chrome_options)
    navegador.maximize_window()
    return navegador

def registrar_resultado(navegador, nome_teste, passou, mensagem=""):
    """Imprime o resultado e tira print em caso de erro."""
    if passou:
        print(f" [PASSOU] {nome_teste}")
    else:
        print(f" [FALHOU] {nome_teste}")
        print(f"          Motivo: {mensagem}")
        
        # Tira print do erro
        nome_limpo = nome_teste.replace(' ', '_').replace('/', '-')
        nome_arquivo = os.path.join(PASTA_PRINTS, f"erro_{nome_limpo}.png")
        navegador.save_screenshot(nome_arquivo)
        print(f"          Screenshot salvo em: {nome_arquivo}")
    print("--------------------------------------------------")