import time
from selenium.webdriver.common.by import By
from utils import iniciar_driver, registrar_resultado, BASE_URL

print("\n=== EXECUTANDO TESTE 05: ADOÇÃO DE ANIMAL ESPECÍFICO ===")
navegador = iniciar_driver()

try:
    teste_nome = "Fluxo de Adoção Completo"
    
    # --- CONFIGURAÇÕES DO TESTE ---
    EMAIL_USER = "aureliano@gmail.com"
    SENHA_USER = "123654"
    NOME_ANIMAL = "Rex Selenium 1764382568" 

    # ==============================================================================
    # ETAPA 1: FAZER LOGIN (Requisito Obrigatório)
    # ==============================================================================
    print(" > Etapa 1: Realizando Login...")
    navegador.get(f"{BASE_URL}/public/login.php")
    
    navegador.find_element(By.NAME, "email").send_keys(EMAIL_USER)
    navegador.find_element(By.NAME, "senha").send_keys(SENHA_USER)
    navegador.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    
    time.sleep(2)
    
    # Verifica se logou
    if "login.php" in navegador.current_url:
        raise Exception("Falha no Login. Verifique as credenciais no script.")

    # ==============================================================================
    # ETAPA 2: ENCONTRAR O ANIMAL ESPECÍFICO
    # ==============================================================================
    print(f" > Etapa 2: Buscando animal '{NOME_ANIMAL}' na galeria...")
    navegador.get(f"{BASE_URL}/public/animais.php")
    
    # Estratégia: Usar XPath para achar o título (h3) que contém o nome do animal
    # e depois subir para o elemento pai (card) para achar o botão dele.
    try:
        # Procura um H3 que tenha o texto do nome
        elemento_titulo = navegador.find_element(By.XPATH, f"//h3[contains(text(), '{NOME_ANIMAL}')]")
        
        # Busca o botão 'Ver Detalhes' dentro do mesmo card-content
        card_content = elemento_titulo.find_element(By.XPATH, "..") # ".." sobe um nível
        botao_detalhes = card_content.find_element(By.TAG_NAME, "a")
        
        botao_detalhes.click()
        time.sleep(1)
        
    except Exception as e:
        raise Exception(f"Animal '{NOME_ANIMAL}' não encontrado na galeria ou erro ao clicar: {e}")

    # ==============================================================================
    # ETAPA 3: PREENCHER FORMULÁRIO DE ADOÇÃO
    # ==============================================================================
    print(" > Etapa 3: Preenchendo solicitação...")
    
    if "solicitar.php" in navegador.current_url:
        navegador.find_element(By.NAME, "telefone").send_keys("(34) 99999-8888")
        navegador.find_element(By.NAME, "endereco").send_keys("Rua dos Testes Automatizados, 500")
        navegador.find_element(By.NAME, "mensagem").send_keys(f"Quero muito adotar o {NOME_ANIMAL}!")
        
        # Rola até o botão (importante em telas pequenas)
        navegador.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(1)
        
        navegador.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        
        time.sleep(2)
        
        # ==============================================================================
        # ETAPA 4: VALIDAÇÃO FINAL
        # ==============================================================================
        # Verifica se foi para 'minhas_solicitacoes' OU se aparece mensagem de sucesso
        url_atual = navegador.current_url
        pag_source = navegador.page_source
        
        if "minhas_solicitacoes.php" in url_atual:
             registrar_resultado(navegador, teste_nome, True)
        elif "sucesso" in pag_source.lower():
             registrar_resultado(navegador, teste_nome, True, "Mensagem de sucesso encontrada.")
        else:
             registrar_resultado(navegador, teste_nome, False, f"Não confirmou envio. URL: {url_atual}")
             
    else:
        registrar_resultado(navegador, teste_nome, False, f"Não carregou página de solicitar. URL: {navegador.current_url}")

except Exception as e:
    registrar_resultado(navegador, "Erro Crítico no Fluxo", False, str(e))

finally:
    navegador.quit()