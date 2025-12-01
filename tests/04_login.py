import time
from selenium.webdriver.common.by import By
from utils import iniciar_driver, registrar_resultado, BASE_URL

print("\n=== EXECUTANDO TESTE 04: LOGIN SIMPLES ===")
navegador = iniciar_driver()

try:
    teste_nome = "Login com Usuário Existente"
    
    # --- DADOS DE TESTE (Altere para um usuário válido do seu banco) ---
    EMAIL_EXISTENTE = "aureliano@gmail.com"  # Exemplo: use um email real do seu banco
    SENHA_EXISTENTE = "123654"              # Senha correspondente
    
    # Passo 1: Acessar página de login
    print(" > Acessando página de login...")
    navegador.get(f"{BASE_URL}/public/login.php")
    
    # Passo 2: Preencher credenciais
    print(" > Preenchendo credenciais...")
    navegador.find_element(By.NAME, "email").send_keys(EMAIL_EXISTENTE)
    navegador.find_element(By.NAME, "senha").send_keys(SENHA_EXISTENTE)
    
    # Passo 3: Enviar
    navegador.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    
    # Espera processamento (Login costuma ser rápido, mas damos 2s)
    time.sleep(2)
    
    # Passo 4: Validação
    url_atual = navegador.current_url
    
    # Verifica se foi redirecionado para a Home (index.php) ou Painel Admin
    if "index.php" in url_atual or "dashboard.php" in url_atual:
        registrar_resultado(navegador, teste_nome, True)
    elif "login.php" in url_atual:
        # Se continuou no login, verifica se tem mensagem de erro
        try:
            msg_erro = navegador.find_element(By.CLASS_NAME, "msg-error").text
            registrar_resultado(navegador, teste_nome, False, f"Falha no login. Mensagem: '{msg_erro}'")
        except:
            registrar_resultado(navegador, teste_nome, False, "Permaneceu na página de login sem mensagem de erro visível.")
    else:
        registrar_resultado(navegador, teste_nome, False, f"Redirecionamento inesperado para: {url_atual}")

except Exception as e:
    registrar_resultado(navegador, "Erro de Execução no Script", False, str(e))

finally:
    navegador.quit()