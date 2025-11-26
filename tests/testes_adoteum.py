import time
from selenium import webdriver
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from webdriver_manager.chrome import ChromeDriverManager

# --- CONFIGURAÇÃO INICIAL ---
print(">>> Iniciando Testes Automatizados do AdoteUm...")
print(">>> Ferramenta: Selenium WebDriver")

# Configura o navegador (instala o driver automaticamente)
service = Service(ChromeDriverManager().install())
navegador = webdriver.Chrome(service=service)

# URL Base (AJUSTE SE SUA PASTA FOR DIFERENTE, EX: adoteum0911)
BASE_URL = "http://localhost/SistemaAd0911"

# Maximiza a janela para testar layout desktop
navegador.maximize_window()

def registrar_log(mensagem):
    print(f"[LOG] {mensagem}")

try:
    # ---------------------------------------------------------
    # TESTE 1: USABILIDADE DE CADASTRO
    # Objetivo: Verificar se um usuário consegue se cadastrar sem erros.
    # ---------------------------------------------------------
    registrar_log("Iniciando Teste 1: Cadastro de Usuário...")
    
    navegador.get(f"{BASE_URL}/user/register.php")
    time.sleep(1) # Espera visual

    # Preenchendo formulário (Usando os 'names' que definimos no HTML)
    email_teste = f"usuario_{int(time.time())}@teste.com" # Cria email único
    
    navegador.find_element(By.NAME, "nome").send_keys("Usuário Teste Selenium")
    navegador.find_element(By.NAME, "email").send_keys(email_teste)
    navegador.find_element(By.NAME, "senha").send_keys("senha123")
    
    registrar_log("Formulário de cadastro preenchido.")
    
    # Clicar no botão de cadastrar (Pela classe do botão que criamos)
    btn_cadastrar = navegador.find_element(By.CSS_SELECTOR, "button[type='submit']")
    btn_cadastrar.click()
    
    time.sleep(2)
    
    # Validação: Se fomos redirecionados para a index ou home
    url_atual = navegador.current_url
    if "index.php" in url_atual or "home.php" in url_atual:
        registrar_log("SUCESSO: Cadastro realizado e redirecionado para Home.")
    else:
        registrar_log(f"FALHA: Redirecionamento incorreto. URL atual: {url_atual}")

    # ---------------------------------------------------------
    # TESTE 2: NAVEGAÇÃO E ESCOLHA DE ANIMAL
    # Objetivo: Verificar se é fácil encontrar e selecionar um animal.
    # ---------------------------------------------------------
    registrar_log("\nIniciando Teste 2: Navegação na Galeria...")
    
    # Ir para a página de animais
    navegador.get(f"{BASE_URL}/animais.php")
    
    # Verificar se existem cards de animais
    cards = navegador.find_elements(By.CLASS_NAME, "card")
    
    if len(cards) > 0:
        registrar_log(f"SUCESSO: Encontrados {len(cards)} animais disponíveis.")
        
        # Clicar no botão 'Ver Detalhes' do primeiro animal
        primeiro_card = cards[0]
        btn_detalhes = primeiro_card.find_element(By.TAG_NAME, "a")
        btn_detalhes.click()
        
        registrar_log("Clicou em 'Ver Detalhes' do primeiro animal.")
        time.sleep(2)
        
        if "solicitar.php" in navegador.current_url:
            registrar_log("SUCESSO: Página de detalhes carregada corretamente.")
        else:
            registrar_log("FALHA: Não abriu a página de solicitar.")
    else:
        registrar_log("ALERTA: Nenhum animal cadastrado para testar. Cadastre um animal no Admin primeiro!")

    # ---------------------------------------------------------
    # TESTE 3: SOLICITAÇÃO DE ADOÇÃO
    # Objetivo: Garantir que o formulário de intenção funciona.
    # ---------------------------------------------------------
    registrar_log("\nIniciando Teste 3: Envio de Solicitação...")
    
    if "solicitar.php" in navegador.current_url:
        # Preenche o formulário de adoção
        navegador.find_element(By.NAME, "telefone").send_keys("(11) 99999-8888")
        navegador.find_element(By.NAME, "endereco").send_keys("Rua dos Testes Automatizados, 100")
        navegador.find_element(By.NAME, "mensagem").send_keys("Olá, este é um teste automatizado de usabilidade via Selenium.")
        
        # Rola a página para baixo para garantir que o botão está visível
        navegador.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(1)

        # Clica em enviar
        navegador.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        time.sleep(2)
        
        # Verifica mensagem de sucesso ou redirecionamento
        # Assumindo que solicitar.php redireciona para minhas_solicitacoes.php ou exibe msg
        pg_conteudo = navegador.page_source
        if "sucesso" in pg_conteudo.lower() or "minhas_solicitacoes.php" in navegador.current_url:
            registrar_log("SUCESSO: Solicitação enviada com êxito!")
        else:
            registrar_log("FALHA: Não foi possível confirmar o envio da solicitação.")

except Exception as e:
    registrar_log(f"ERRO CRÍTICO DURANTE O TESTE: {e}")

finally:
    # Fecha o navegador após 5 segundos
    registrar_log("\n>>> Testes finalizados. Fechando navegador em 5s...")
    time.sleep(5)
    navegador.quit()