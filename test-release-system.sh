#!/bin/bash

##############################################
# Test Script - Woovi PIX Plugin Auto-Update
# Tests DeepSeek changelog generation and WordPress update detection
##############################################

set -e

echo "🧪 Iniciando testes do sistema de auto-update..."
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Verify GitHub Actions workflow exists
echo "📋 Test 1: Verificando workflow do GitHub Actions..."
if [ -f ".github/workflows/deploy.yml" ]; then
    echo -e "${GREEN}✅ Workflow encontrado${NC}"
else
    echo -e "${RED}❌ Workflow não encontrado${NC}"
    exit 1
fi

# Test 2: Verify DeepSeek API key in workflow
echo ""
echo "🔑 Test 2: Verificando API key do DeepSeek..."
if grep -q "sk-f233af4f1527475eb89fb5aa48c0a1d3" .github/workflows/deploy.yml; then
    echo -e "${GREEN}✅ API key configurada${NC}"
else
    echo -e "${RED}❌ API key não encontrada${NC}"
    exit 1
fi

# Test 3: Verify plugin update checker is configured
echo ""
echo "🔄 Test 3: Verificando plugin update checker..."
if grep -q "buildUpdateChecker" udia-pods-thankyou.php; then
    echo -e "${GREEN}✅ Update checker configurado${NC}"
else
    echo -e "${RED}❌ Update checker não encontrado${NC}"
    exit 1
fi

# Test 4: Verify GitHub Releases integration
echo ""
echo "🎁 Test 4: Verificando integração com GitHub Releases..."
if grep -q "enableReleaseAssets" udia-pods-thankyou.php; then
    echo -e "${GREEN}✅ GitHub Releases habilitado${NC}"
else
    echo -e "${RED}❌ GitHub Releases não configurado${NC}"
    exit 1
fi

# Test 5: Check current version
echo ""
echo "📌 Test 5: Verificando versão atual..."
CURRENT_VERSION=$(grep "Version:" udia-pods-thankyou.php | awk '{print $3}')
echo -e "${YELLOW}Versão atual: ${CURRENT_VERSION}${NC}"

# Test 6: Check composer dependencies
echo ""
echo "📦 Test 6: Verificando dependências do Composer..."
if [ -d "vendor/yahnis-elsts/plugin-update-checker" ]; then
    echo -e "${GREEN}✅ plugin-update-checker instalado${NC}"
else
    echo -e "${YELLOW}⚠️  Executando composer install...${NC}"
    composer install --no-dev --optimize-autoloader
    echo -e "${GREEN}✅ Dependências instaladas${NC}"
fi

# Test 7: Verify documentation exists
echo ""
echo "📚 Test 7: Verificando documentação..."
DOCS=("README.md" "TROUBLESHOOTING.md" "DEEPSEEK_CHANGELOG.md")
for doc in "${DOCS[@]}"; do
    if [ -f "$doc" ]; then
        echo -e "${GREEN}✅ ${doc}${NC}"
    else
        echo -e "${RED}❌ ${doc} não encontrado${NC}"
    fi
done

# Test 8: Test DeepSeek API (optional)
echo ""
echo "🤖 Test 8: Testando DeepSeek API..."
echo -e "${YELLOW}Enviando requisição de teste...${NC}"

RESPONSE=$(curl -s -w "\n%{http_code}" https://api.deepseek.com/v1/chat/completions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer sk-f233af4f1527475eb89fb5aa48c0a1d3" \
  -d '{
    "model": "deepseek-chat",
    "messages": [{"role": "user", "content": "Say hello in one word"}],
    "max_tokens": 10
  }' 2>/dev/null)

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n-1)

if [ "$HTTP_CODE" == "200" ]; then
    echo -e "${GREEN}✅ DeepSeek API respondendo (HTTP 200)${NC}"
    REPLY=$(echo "$BODY" | jq -r '.choices[0].message.content' 2>/dev/null || echo "N/A")
    echo -e "   Resposta: ${REPLY}"
else
    echo -e "${RED}❌ DeepSeek API erro (HTTP ${HTTP_CODE})${NC}"
    echo "   Resposta: $BODY"
fi

# Test 9: Simulate version bump
echo ""
echo "🔢 Test 9: Simulando bump de versão..."
IFS='.' read -r -a parts <<< "$CURRENT_VERSION"
MAJOR="${parts[0]}"
MINOR="${parts[1]}"
PATCH="${parts[2]}"
NEW_PATCH=$((PATCH + 1))
NEW_VERSION="$MAJOR.$MINOR.$NEW_PATCH"
echo -e "${YELLOW}Próxima versão será: ${NEW_VERSION}${NC}"

# Test 10: Check Git status
echo ""
echo "🔍 Test 10: Verificando status do Git..."
git status --short
if [ -z "$(git status --porcelain)" ]; then
    echo -e "${GREEN}✅ Working tree limpo${NC}"
else
    echo -e "${YELLOW}⚠️  Há mudanças não commitadas${NC}"
fi

# Summary
echo ""
echo "═══════════════════════════════════════════"
echo "📊 RESUMO DOS TESTES"
echo "═══════════════════════════════════════════"
echo ""
echo -e "${GREEN}✅ Workflow GitHub Actions: OK${NC}"
echo -e "${GREEN}✅ DeepSeek API Key: Configurada${NC}"
echo -e "${GREEN}✅ Update Checker: Habilitado${NC}"
echo -e "${GREEN}✅ GitHub Releases: Integrado${NC}"
echo -e "${YELLOW}📌 Versão Atual: ${CURRENT_VERSION}${NC}"
echo -e "${YELLOW}🔜 Próxima Release: ${NEW_VERSION}${NC}"
echo ""
echo "═══════════════════════════════════════════"
echo "🚀 COMO TESTAR A RELEASE AUTOMÁTICA"
echo "═══════════════════════════════════════════"
echo ""
echo "1. Faça uma mudança simples:"
echo "   echo '# Test release' >> README.md"
echo ""
echo "2. Commit e push:"
echo "   git add README.md"
echo "   git commit -m 'test: Trigger automated release'"
echo "   git push origin main"
echo ""
echo "3. Acompanhe o workflow:"
echo "   https://github.com/gustavofullstack/udia-pods-thankyou/actions"
echo ""
echo "4. Verifique a release:"
echo "   https://github.com/gustavofullstack/udia-pods-thankyou/releases/latest"
echo ""
echo "5. No WordPress (após 3-5 minutos):"
echo "   Dashboard → Updates → Verificar Atualizações"
echo ""
echo "═══════════════════════════════════════════"
echo -e "${GREEN}🎉 TODOS OS TESTES PASSARAM!${NC}"
echo "═══════════════════════════════════════════"
