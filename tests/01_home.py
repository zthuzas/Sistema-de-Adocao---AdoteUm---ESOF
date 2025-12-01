from utils import iniciar_driver, registrar_resultado, BASE_URL

print("\n=== EXECUTANDO TESTE 01: HOME PAGE ===")
navegador = iniciar_driver()

try:
    teste_nome = "Acesso à Home Page"
    
    # Passo 1: Acessar a página
    navegador.get(f"{BASE_URL}/public/index.php")
    
    # Passo 2: Validar Título ou URL
    titulo = navegador.title
    if "AdoteUm" in titulo or "index.php" in navegador.current_url:
        registrar_resultado(navegador, teste_nome, True)
    else:
        registrar_resultado(navegador, teste_nome, False, f"Título incorreto: '{titulo}'")

except Exception as e:
    registrar_resultado(navegador, "Erro de Execução", False, str(e))

finally:
    navegador.quit()