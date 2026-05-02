{{-- Espera opcional: $registerModal (bool), $fromRegister (bool) --}}
@php
    $registerModal = $registerModal ?? false;
    $fromRegister = $fromRegister ?? false;
@endphp

<div class="prose prose-gray max-w-none prose-headings:font-semibold prose-h2:text-xl">
    <p class="not-prose mb-8 text-gray-600"><strong>Última actualização:</strong> 27 de julho de 2025 · Versão de controlo na app: {{ config('legal.terms_version') }}</p>

    <section class="mb-8">
        <h2 class="mb-4">1. Aceitação dos Termos</h2>
        <p class="text-gray-700">
            Ao aceder e utilizar a plataforma GratoAI, concorda em cumprir estes Termos de Uso. Se não concordar com qualquer parte, não deve utilizar os serviços.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">2. Descrição do Serviço</h2>
        <p class="text-gray-700 mb-4">
            A GratoAI oferece assistentes de inteligência artificial (por exemplo preparação para entrevistas, optimização para ATS e outras funções relacionadas à carreira e produtividade).
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">3. Registo e conta</h2>
        <p class="text-gray-700 mb-4">
            Concorda em fornecer dados correctos e manter palavras-passe e acesso em segurança, notificando-nos sobre usos não autorizados.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">4. Utilização aceitável</h2>
        <ul class="list-disc pl-6 text-gray-700">
            <li>É proibido uso ilegal, tentativa de perturbação dos sistemas, partilha indevida de credenciais, uso automatizado não autorizado, violação de direitos ou conteúdo ilícito.</li>
        </ul>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">5. Pagamentos e subscrições</h2>
        <p class="text-gray-700 mb-4">
            Preços podem estar em moeda nacional; pagamentos tratados por prestadores externos. Cancelamentos e reembolsos seguem a política então vigente.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">6. Propriedade intelectual</h2>
        <p class="text-gray-700">
            A plataforma, marcas e software são propriedade da GratoAI ou licenciados; o resultado gerado para o seu uso pessoal segue os limites legais e contratuais indicados aos utilizadores.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">7. Privacidade</h2>
        <p class="text-gray-700 mb-4 not-prose">
            O tratamento de dados pessoais rege-se pela
            @if ($registerModal)
                <button type="button" class="register-open-privacy-modal font-medium text-indigo-700 hover:underline cursor-pointer bg-transparent border-0 p-0 inline">Política de Privacidade</button>,
            @else
                <a href="{{ route('privacidade').($fromRegister ? '?from=register' : '') }}" class="font-medium text-indigo-700 hover:underline">Política de Privacidade</a>,
            @endif
            parte integrante destes termos.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">8. Limitação de responsabilidade</h2>
        <p class="text-gray-700">
            Os serviços são prestados no estado actual; não garantimos ausência total de falhas nem resultados específicos. A responsabilidade aplica-se na medida máxima permitida pela lei aplicável.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">9. Rescisão</h2>
        <p class="text-gray-700">
            Podemos suspender conta em caso de violação grave; também pode solicitar encerramento. Podem aplicar-se regras de retenção e obrigações pendentes de pagamento.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">10. Alterações aos termos</h2>
        <p class="text-gray-700">
            Alterações relevantes poderão comunicar-se na plataforma ou por outros meios conforme lei; novo aceite poderá ser necessário mediante actualização das versões legais.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">11. Lei aplicável</h2>
        <p class="text-gray-700">
            Regidos pela lei brasileira, com tribunais competentes em São Paulo, SP, onde aplicável, salvo disposição legal em contrário.
        </p>
    </section>

    <section class="mb-8">
        <h2 class="mb-4">12. Contacto</h2>
        <div class="rounded-lg bg-gray-50 p-4 not-prose">
            <p class="text-gray-700"><strong>E-mail:</strong> <a href="mailto:{{ config('legal.contact_email') }}" class="text-indigo-700 hover:underline">{{ config('legal.contact_email') }}</a></p>
        </div>
    </section>

    <div class="not-prose border-t border-gray-200 pt-6">
        <p class="text-sm text-gray-500">
            Ao utilizar a plataforma GratoAI, confirma que leu e concorda em estar vinculado a estes Termos de Uso quanto aplicável ao seu uso.
        </p>
    </div>
</div>
