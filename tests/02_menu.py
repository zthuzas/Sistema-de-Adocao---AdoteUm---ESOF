from selenium.webdriver.common.by import By
from utils import iniciar_driver, registrar_resultado, BASE_URL

print("\n=== EXECUTANDO TESTE 02: MENU DE NAVEGAÇÃO ===")
navegador = iniciar_driver()

try:
    # Acessa a home para ver o menu
    navegador.get(f"{BASE_URL}/public/index.php")

    teste_nome = "Visibilidade do Link 'Animais'"
    try:
        link_animais = navegador.find_element(By.LINK_TEXT, "Animais")
        if link_animais.is_displayed():
            registrar_resultado(navegador, teste_nome, True)
        else:
            registrar_resultado(navegador, teste_nome, False, "Link existe mas está invisível")
    except:
        registrar_resultado(navegador, teste_nome, False, "Link 'Animais' não encontrado no HTML")

except Exception as e:
    registrar_resultado(navegador, "Erro de Execução", False, str(e))

finally:
    navegador.quit()