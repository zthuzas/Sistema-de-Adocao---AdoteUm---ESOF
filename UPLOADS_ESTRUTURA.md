📋 ESTRUTURA DE IMAGENS - UPLOAD DE ANIMAIS
═══════════════════════════════════════════════════════════════════════════════

🗂️ ONDE ESTÃO AS IMAGENS?

Localização física:
└─ c:\Program Files\VertrigoServ\www\SistemaAd0911\uploads\animals\

Arquivos armazenados:
├─ 1763231911_a8968a6d431a.jpg
├─ 1763231946_c63390d67c63.jpg
└─ 1763298884_885f88aadda3.jpg

═══════════════════════════════════════════════════════════════════════════════

🔗 COMO FUNCIONAM AS URLS

1. Configuração Base (config/config.php):
   define('BASE_URL', '/SistemaAd0911');

2. URL da Imagem no Banco:
   /SistemaAd0911/uploads/animals/1763231911_a8968a6d431a.jpg

3. Caminho no servidor:
   http://localhost/SistemaAd0911/uploads/animals/1763231911_a8968a6d431a.jpg

═══════════════════════════════════════════════════════════════════════════════

📊 FLUXO DE UPLOAD

1. Admin faz upload via dashboard_admin.php
2. PHP processa:
   ├─ Valida formato (JPG/PNG)
   ├─ Redimensiona se maior que 1024x1024
   ├─ Salva em: /uploads/animals/{timestamp}_{random}.jpg
   └─ Armazena URL no banco: /SistemaAd0911/uploads/animals/{arquivo}

3. Ao exibir em animais.php:
   ├─ Busca imagem_url do banco
   ├─ Exibe em <img src="...">
   └─ Navegador carrega: /SistemaAd0911/uploads/animals/{arquivo}

═══════════════════════════════════════════════════════════════════════════════

✅ O QUE ESTÁ FUNCIONANDO

✓ Pasta /uploads/animals/ criada e acessível
✓ 3 imagens já foram uploadadas com sucesso
✓ URLs armazenadas corretamente no banco
✓ Imagens exibem corretamente na página de animais
✓ Nomes de arquivo são únicos (timestamp + random hex)
✓ Redimensionamento automático funcionando

═══════════════════════════════════════════════════════════════════════════════

🔍 COMO VERIFICAR

1. Verificar pasta:
   ls -la c:\Program Files\VertrigoServ\www\SistemaAd0911\uploads\animals\

2. Verificar banco:
   SELECT id, nome, imagem_url FROM animais;

3. Verificar URL no navegador:
   http://localhost/SistemaAd0911/uploads/animals/1763231911_a8968a6d431a.jpg

═══════════════════════════════════════════════════════════════════════════════

⚠️ POSSÍVEIS PROBLEMAS E SOLUÇÕES

Problema 1: Imagem não carrega no navegador
├─ Solução 1: Verificar BASE_URL em config.php
├─ Solução 2: Verificar permissões da pasta /uploads/
└─ Solução 3: Verificar se arquivo existe no disco

Problema 2: Upload falha com "Imagem muito grande"
├─ Limite: 2MB (ajustável em dashboard_admin.php linha 44)
└─ Solução: Comprimir imagem antes de fazer upload

Problema 3: Apenas JPG aparece, PNG não carrega
├─ Verificar extensão salva no arquivo
├─ PNG mantém transparência ao redimensionar
└─ Ambos são suportados (image/jpeg e image/png)

═══════════════════════════════════════════════════════════════════════════════

🛠️ MANUTENÇÃO

1. Limpar imagens antigas:
   └─ Deletar arquivo físico
   └─ Deletar registro do banco (se animal foi deletado)

2. Backup de imagens:
   └─ Fazer backup de /uploads/animals/

3. Mover imagens para novo servidor:
   └─ Copiar pasta /uploads/animals/
   └─ Manter URLs iguais

═══════════════════════════════════════════════════════════════════════════════

📚 REFERÊNCIAS NO CÓDIGO

Dashboard Admin (upload):
└─ public/dashboard_admin.php (linhas 40-90)

Exibição (animais.php):
└─ public/animais.php (linha 92)

Exibição (index.php):
└─ public/index.php (mesmo padrão)

═══════════════════════════════════════════════════════════════════════════════

Tudo está funcionando corretamente! As imagens estão sendo armazenadas e exibidas
corretamente. Se tiver problemas específicos, avise.

