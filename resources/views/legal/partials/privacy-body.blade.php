{{-- Texto da política (páginas públicas e modal de registro). --}}
<p class="mb-8 text-gray-600">Última atualização referida no texto: Julho 2025 · Versão de controle na app: {{ config('legal.privacy_policy_version') }}</p>

<div class="space-y-6 text-base leading-relaxed text-gray-800">
    <p>
        Esta Política de Privacidade descreve como o GratoAI coleta, usa, armazena e compartilha as suas informações pessoais quando você utiliza a nossa plataforma.
    </p>

    <h2 class="text-xl font-bold text-gray-900">1. Dados que coletamos</h2>
    <ul class="list-disc space-y-1 pl-6">
        <li>Nome completo, e-mail, telefone e documento fiscal (CPF ou CNPJ).</li>
        <li>Foto de perfil, depoimentos e histórico de utilização dos agentes.</li>
        <li>Endereço (CEP, rua, número, cidade, estado).</li>
        <li>Dados necessários aos pagamentos, quando aplicável.</li>
        <li>Currículos, vagas e conteúdo que você fornecer aos agentes.</li>
        <li>Informação de navegação e cookies, conforme a sua configuração do navegador.</li>
    </ul>

    <h2 class="text-xl font-bold text-gray-900">2. Finalidades</h2>
    <ul class="list-disc space-y-1 pl-6">
        <li>Gerir a sua conta e transações.</li>
        <li>Prestar e melhorar os serviços e a segurança.</li>
        <li>Cumprir obrigações legais e regulamentares.</li>
    </ul>

    <h2 class="text-xl font-bold text-gray-900">3. Compartilhamento</h2>
    <ul class="list-disc space-y-1 pl-6">
        <li>Prestadores como o processador de pagamentos (ex.: Asaas), quando necessário à cobrança.</li>
        <li>Autoridades, quando legalmente obrigatório.</li>
    </ul>

    <h2 class="text-xl font-bold text-gray-900">4. Os seus direitos (LGPD)</h2>
    <p>Incluem acesso, correção, exclusão, portabilidade e informação sobre tratamentos.</p>
    <p class="rounded-lg bg-gray-50 p-4 text-gray-700">
        Contato: <a href="mailto:{{ config('legal.contact_email') }}" class="font-medium text-indigo-700 hover:underline">{{ config('legal.contact_email') }}</a>
    </p>

    <h2 class="text-xl font-bold text-gray-900">5. Cookies</h2>
    <p>
        Utilizamos cookies conforme necessário ao funcionamento e à análise. Você pode configurar o navegador para limitá-los.
    </p>
</div>
