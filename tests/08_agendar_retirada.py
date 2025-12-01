import time
import datetime
import random
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys # Importante para enviar teclas especiais
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from utils import iniciar_driver, registrar_resultado, BASE_URL

print("\n=== EXECUTANDO TESTE 08: AGENDAMENTO DE RETIRADA (ADMIN) ===")
navegador = iniciar_driver()
wait = WebDriverWait(navegador, 10)

try:
    teste_nome = "Fluxo de Operações: Agendar Retirada"
    
    # --- CONFIGURAÇÕES ---
    EMAIL_OPS = "operacoes@gmail.com" 
    SENHA_OPS = "123654"

    # ==============================================================================
    # ETAPA 1: LOGIN
    # ==============================================================================
    print(" > Etapa 1: Logando no sistema...")
    navegador.get(f"{BASE_URL}/public/login.php")
    
    navegador.find_element(By.NAME, "email").send_keys(EMAIL_OPS)
    navegador.find_element(By.NAME, "senha").send_keys(SENHA_OPS)
    navegador.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    
    try:
        wait.until(EC.url_contains("dashboard"))
    except:
        print("   -> Aviso: Timeout esperando redirecionamento.")

    print(f"   -> URL Atual: {navegador.current_url}")

    # ==============================================================================
    # ETAPA 2: LOCALIZAR E CLICAR NO BOTÃO 'AGENDAR'
    # ==============================================================================
    print(" > Etapa 2: Buscando botão 'Agendar'...")
    
    try:
        botao_agendar = wait.until(EC.presence_of_element_located((By.XPATH, "//button[contains(., 'Agendar')]")))
        navegador.execute_script("arguments[0].scrollIntoView({block: 'center'});", botao_agendar)
        time.sleep(1)
        print("   -> Botão encontrado. Clicando...")
        navegador.execute_script("arguments[0].click();", botao_agendar)
        
    except Exception as e:
        print("   -> Botão não encontrado de imediato. Tentando aba 'Solicitações'...")
        try:
            aba = navegador.find_element(By.XPATH, "//a[contains(@href, 'tab=solicitacoes')]")
            navegador.execute_script("arguments[0].click();", aba)
            time.sleep(2)
            botao_agendar = wait.until(EC.presence_of_element_located((By.XPATH, "//button[contains(., 'Agendar')]")))
            navegador.execute_script("arguments[0].scrollIntoView({block: 'center'});", botao_agendar)
            navegador.execute_script("arguments[0].click();", botao_agendar)
        except:
            raise Exception("Não encontrei o botão 'Agendar'.")

    # ==============================================================================
    # ETAPA 3: PREENCHER MODAL (DATA DD/MM/AAAA)
    # ==============================================================================
    print(" > Etapa 3: Preenchendo formulário no modal...")
    
    # Prepara dados
    amanha = datetime.date.today() + datetime.timedelta(days=3)
    
    # Formato para digitar direto (apenas números, dia -> mes -> ano): ddmmyyyy
    data_numeros = amanha.strftime("%d%m%Y")
    
    # Hora: Formato hhmm
    hora_numeros = "1430"
    
    obs = f"Agendamento Selenium - {int(time.time())}"
    
    # Aguarda campo visível
    wait.until(EC.visibility_of_element_located((By.NAME, "data_retirada")))
    
    try:
        # DATA
        print(f"   -> Inserindo Data: {data_numeros}")
        campo_data = navegador.find_element(By.NAME, "data_retirada")
        campo_data.clear()
        time.sleep(0.5)
        # Digita os números sequencialmente (01 -> 01 -> 2025)
        campo_data.send_keys(data_numeros)
        
        # HORA
        print(f"   -> Inserindo Hora: {hora_numeros}")
        campo_hora = navegador.find_element(By.NAME, "hora_retirada")
        campo_hora.send_keys(hora_numeros)
            
        time.sleep(0.5)
        
        # OBSERVAÇÕES
        print("   -> Inserindo Observações...")
        navegador.find_element(By.NAME, "observacoes").send_keys(obs)
        
        time.sleep(1)
        
        # SALVAR
        print("   -> Salvando...")
        try:
            btn_salvar = navegador.find_element(By.XPATH, "//button[contains(., 'Salvar') or contains(., 'Confirmar')]")
            navegador.execute_script("arguments[0].click();", btn_salvar)
        except:
            navegador.execute_script("document.querySelector('form[action*=\"agendar\"] button[type=\"submit\"]').click()")
            
        time.sleep(3)

    except Exception as e:
        navegador.save_screenshot("tests/evidencias/debug_form_agendamento.png")
        print(f"   -> Erro: {e}")
        raise Exception("Falha ao preencher modal.")

    # ==============================================================================
    # ETAPA 4: VALIDAÇÃO FINAL
    # ==============================================================================
    print(" > Etapa 4: Validando...")
    
    pg_source = navegador.page_source
    
    if "agendad" in pg_source.lower() or "sucesso" in pg_source.lower():
         registrar_resultado(navegador, teste_nome, True, "Agendamento concluído com sucesso.")
    elif "Aguardando Retirada" in pg_source:
         registrar_resultado(navegador, teste_nome, True, "Status mudou para 'Aguardando Retirada'.")
    else:
         navegador.save_screenshot("tests/evidencias/erro_agendamento.png")
         registrar_resultado(navegador, teste_nome, False, "Confirmação visual falhou.")

except Exception as e:
    registrar_resultado(navegador, "Erro Crítico", False, str(e))

finally:
    navegador.quit()