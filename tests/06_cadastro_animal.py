import time
import os
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from utils import iniciar_driver, registrar_resultado, BASE_URL

print("\n=== EXECUTANDO TESTE 06: CADASTRO DE ANIMAL (ADMIN) ===")
navegador = iniciar_driver()

try:
    teste_nome = "Fluxo de Admin: Cadastro de Novo Animal"
    
    # --- CONFIGURAÇÕES ---
    EMAIL_ADMIN = "admin@gmail.com" 
    SENHA_ADMIN = "123456"
    NOME_IMAGEM = "animalteste.jpg" 
    
    # Caminho absoluto da imagem
    caminho_script = os.path.dirname(os.path.abspath(__file__))
    caminho_imagem_completo = os.path.join(caminho_script, NOME_IMAGEM)
    
    if not os.path.exists(caminho_imagem_completo):
        raise Exception(f"Imagem '{NOME_IMAGEM}' não encontrada na pasta tests.")

    # ==============================================================================
    # ETAPA 1: LOGIN COMO ADMIN
    # ==============================================================================
    print(" > Etapa 1: Logando como Admin...")
    navegador.get(f"{BASE_URL}/public/login.php")
    
    navegador.find_element(By.NAME, "email").send_keys(EMAIL_ADMIN)
    navegador.find_element(By.NAME, "senha").send_keys(SENHA_ADMIN)
    navegador.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    
    time.sleep(2)
    
    # Valida se estamos no Dashboard
    url_atual = navegador.current_url
    print(f"   -> URL Pós-Login: {url_atual}")
    
    # Verifica palavras-chave de sucesso
    if "login.php" in url_atual:
         raise Exception("O login falhou (permaneceu na página de login). Verifique se o usuário 'admin@email.com' existe e tem permissão de admin.")

    # ==============================================================================
    # ETAPA 2: PREENCHER DADOS NO DASHBOARD
    # ==============================================================================
    print(" > Etapa 2: Preenchendo formulário de cadastro...")
    
    nome_animal = f"Rex Selenium {int(time.time())}"
    
    try:
        # 1. Preenche Nome
        print("   -> [1/5] Preenchendo Nome...")
        navegador.find_element(By.NAME, "nome").send_keys(nome_animal)
        time.sleep(1)
        
        # 2. Preenche Tipo (AGORA COMO TEXTO, baseado no seu código)
        print("   -> [2/5] Preenchendo Tipo/Espécie...")
        # Seu código usa <input type="text" name="tipo">
        navegador.find_element(By.NAME, "tipo").send_keys("Cachorro")
        time.sleep(1)

        # 3. Preenche Idade
        print("   -> [3/5] Preenchendo Idade...")
        navegador.find_element(By.NAME, "idade").send_keys("3 anos")
        time.sleep(1)
        
        # 4. Preenche Descrição
        print("   -> [4/5] Preenchendo Descrição...")
        navegador.find_element(By.NAME, "descricao").send_keys("Cadastro realizado via automação.")
        time.sleep(1)

        # 5. Upload da Imagem (CORRIGIDO: name='imagem_file')
        print("   -> [5/5] Fazendo Upload da Imagem...")
        # Seu código usa <input type="file" name="imagem_file">
        navegador.find_element(By.NAME, "imagem_file").send_keys(caminho_imagem_completo)
        time.sleep(1)
        
        # Enviar Formulário
        print("   -> Enviando formulário...")
        # Busca botão que contém o texto "Cadastrar" ou pelo tipo submit
        try:
            navegador.find_element(By.XPATH, "//button[contains(text(), 'Cadastrar')]").click()
        except:
            navegador.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        
        time.sleep(3) 

    except Exception as e:
        navegador.save_screenshot("tests/evidencias/debug_formulario.png")
        raise Exception(f"Erro ao interagir com o formulário: {e}")

    # ==============================================================================
    # ETAPA 3: VALIDAÇÃO
    # ==============================================================================
    print(" > Etapa 3: Validando cadastro...")
    
    pg_source = navegador.page_source
    
    # Verifica se o animal aparece na tabela OU se tem mensagem de sucesso
    if nome_animal in pg_source:
            registrar_resultado(navegador, teste_nome, True, f"Sucesso! Animal '{nome_animal}' encontrado na tabela.")
    elif "Animal cadastrado com sucesso" in pg_source:
            registrar_resultado(navegador, teste_nome, True, "Mensagem de sucesso detectada.")
    else:
            navegador.save_screenshot("tests/evidencias/erro_validacao.png")
            registrar_resultado(navegador, teste_nome, False, "O animal não apareceu na lista e nenhuma mensagem de sucesso foi vista.")

except Exception as e:
    registrar_resultado(navegador, "Erro Crítico no Teste", False, str(e))

finally:
    navegador.quit()