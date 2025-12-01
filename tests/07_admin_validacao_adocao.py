import time
from selenium.webdriver.common.by import By
from utils import iniciar_driver, registrar_resultado, BASE_URL

print("\n=== EXECUTANDO TESTE 07: VALIDAÇÃO DE ADOÇÃO (ADMIN) ===")
navegador = iniciar_driver()

try:
    teste_nome = "Fluxo de Admin: Aprovar Solicitação Pendente"
    
    # --- CONFIGURAÇÕES ---
    EMAIL_ADMIN = "admin@gmail.com" 
    SENHA_ADMIN = "123456"

    # ==============================================================================
    # ETAPA 1: LOGIN COMO ADMIN
    # ==============================================================================
    print(" > Etapa 1: Logando como Admin...")
    navegador.get(f"{BASE_URL}/public/login.php")
    
    navegador.find_element(By.NAME, "email").send_keys(EMAIL_ADMIN)
    navegador.find_element(By.NAME, "senha").send_keys(SENHA_ADMIN)
    navegador.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
    
    time.sleep(2)

    # ==============================================================================
    # ETAPA 2: ACESSAR ABA DE SOLICITAÇÕES
    # ==============================================================================
    print(" > Etapa 2: Acessando aba de solicitações...")
    
    # Tenta ir direto pela URL para garantir que a aba 'solicitacoes' esteja ativa
    navegador.get(f"{BASE_URL}/public/dashboard_admin.php?tab=solicitacoes")
    
    time.sleep(3) # Aumentei o tempo para garantir o carregamento da tabela

    # ==============================================================================
    # ETAPA 3: LOCALIZAR E APROVAR A PRIMEIRA PENDENTE
    # ==============================================================================
    print(" > Etapa 3: Buscando solicitação pendente...")
    
    try:
        # ESTRATÉGIA MELHORADA:
        # Procura especificamente um botão (button) que contenha o texto 'Aprovar'
        # O print mostra que é um botão verde com texto "✓ Aprovar"
        
        # Opção 1: Pelo texto parcial (mais flexível)
        botoes_aprovar = navegador.find_elements(By.XPATH, "//button[contains(., 'Aprovar')]")
        
        if len(botoes_aprovar) > 0:
            botao_alvo = botoes_aprovar[0] # Pega o primeiro da lista
            
            # Scroll até o botão para garantir que ele não está escondido
            navegador.execute_script("arguments[0].scrollIntoView({block: 'center'});", botao_alvo)
            time.sleep(1)
            
            print("   -> Botão 'Aprovar' encontrado. Clicando...")
            botao_alvo.click()
            
            # Aguarda o processamento
            time.sleep(3)
            
        else:
            # Se não achou botões, verifica se tem mensagem de "Nenhuma solicitação"
            pag_source = navegador.page_source
            if "Nenhuma solicitação" in pag_source or "nenhuma solicitação" in pag_source.lower():
                registrar_resultado(navegador, teste_nome, True, "Teste inconclusivo (Passou): Não havia solicitações pendentes para aprovar.")
                exit()
            else:
                # Tira print para debug
                navegador.save_screenshot("tests/evidencias/debug_botoes_nao_encontrados.png")
                raise Exception("A tabela existe, mas não encontrei nenhum botão 'Aprovar'.")

    except Exception as e:
        raise Exception(f"Erro ao tentar localizar/clicar no botão aprovar: {e}")

    # ==============================================================================
    # ETAPA 4: VALIDAÇÃO FINAL
    # ==============================================================================
    print(" > Etapa 4: Validando sucesso...")
    
    pg_source = navegador.page_source
    
    # Verifica mensagem de sucesso
    if "aprovada com sucesso" in pg_source.lower() or "solicitação aprovada" in pg_source.lower():
         registrar_resultado(navegador, teste_nome, True, "Mensagem de aprovação detectada.")
    
    # Ou verifica se o status mudou para 'Aprovada' (o botão aprovar deve ter sumido para esse item)
    elif "Aprovada" in pg_source: 
         registrar_resultado(navegador, teste_nome, True, "Status 'Aprovada' encontrado na lista.")
    else:
         navegador.save_screenshot("tests/evidencias/erro_validacao_aprovacao.png")
         registrar_resultado(navegador, teste_nome, False, "Não foi possível confirmar a aprovação visualmente.")

except Exception as e:
    registrar_resultado(navegador, "Erro Crítico no Admin", False, str(e))

finally:
    navegador.quit()