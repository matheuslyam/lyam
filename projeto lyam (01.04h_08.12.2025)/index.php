<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descubra sua E-Bike Ideal | Lyam Bikes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0b1726;
        }

        ::-webkit-scrollbar-thumb {
            background: #2A3B4D;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #a3e635;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            base: '#0b1726',    // Dark Background
                            card: '#2A3B4D',    // Slate/Green Dark Card
                            neon: '#a3e635',    // Lime-400 (Neon Green)
                            accent: '#84cc16',  // Lime-500 (Darker Neon)
                            surface: '#334155', // Slate-700
                        }
                    },
                    boxShadow: {
                        'glow': '0 0 20px rgba(163, 230, 53, 0.15)',
                        'neon': '0 0 25px rgba(163, 230, 53, 0.4)',
                        'card': '0 25px 50px -12px rgba(0, 0, 0, 0.6)',
                    },
                    animation: {
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
</head>

<body
    class="bg-brand-base text-white h-screen flex flex-col overflow-hidden selection:bg-brand-neon selection:text-brand-base"
    x-data="quizApp()">

    <!-- Progress Bar -->
    <div class="w-full h-2 fixed top-0 left-0 z-50 bg-brand-base/50 backdrop-blur-sm">
        <div class="h-full bg-brand-neon shadow-[0_0_15px_rgba(163,230,53,0.6)] transition-all duration-700 ease-in-out"
            :style="'width: ' + progress + '%'"></div>
    </div>

    <!-- Main Container -->
    <!-- FIX: Changed overflow-hidden to overflow-y-auto to ensure content is reachable on small screens -->
    <main class="flex-1 flex flex-col justify-center items-center relative w-full h-full overflow-y-auto overflow-x-hidden">

        <!-- Background Image (Visible on Step 0) -->
        <div class="absolute inset-0 z-0 transition-opacity duration-1000"
            :class="step === 0 ? 'opacity-40' : 'opacity-0 pointer-events-none'">
            <img src="https://placehold.co/1200x800/1e3a8a/ffffff?text=E-Bike+Urbana+Premium" alt="Background"
                class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-b from-brand-base/90 via-brand-base/70 to-brand-base"></div>
        </div>

        <!-- Step 0: Splash Screen -->
        <div x-show="step === 0" x-transition:leave="transition ease-in duration-500 opacity-0 -translate-x-20"
            class="relative z-10 text-center px-6 max-w-4xl mx-auto flex flex-col items-center justify-center min-h-full py-10 space-y-10">

            <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-white leading-tight drop-shadow-lg">
                NÃO COMPRE A<br /><span class="text-brand-neon">BIKE ERRADA.</span>
            </h1>

            <p class="text-lg md:text-xl text-slate-300 max-w-2xl font-medium leading-relaxed drop-shadow-md">
                Responda 3 perguntas rápidas para descobrir qual modelo aguenta o seu trajeto real e verifique se você é
                elegível para o <span class="text-brand-neon font-bold">Bônus de Acessórios Blindados (R$ 300 a R$
                    500)</span>.
            </p>

            <div class="pt-6 flex flex-col items-center space-y-4">
                <button @click="nextStep()"
                    class="group relative inline-flex items-center justify-center px-10 py-5 font-bold text-brand-base transition-all duration-200 bg-brand-neon rounded-full hover:bg-white hover:scale-105 hover:shadow-neon focus:outline-none focus:ring-4 focus:ring-brand-neon/50">
                    <span class="mr-2 text-xl tracking-wide">👉 INICIAR CONFIGURADOR</span>
                    <svg class="w-6 h-6 transition-transform duration-200 group-hover:translate-x-1" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
                <span class="text-sm text-slate-500 font-medium tracking-wide">Leva menos de 30 segundos</span>
            </div>
        </div>

        <!-- Quiz Steps Container (Steps 1-3 & Contact) -->
        <!-- FIX: Added z-20 to ensure it sits above background -->
        <div x-show="step > 0 && step !== 'processing'" x-cloak
            class="relative z-20 w-full max-w-2xl px-4 py-10 transition-all duration-500">

            <!-- Card -->
            <div
                class="bg-brand-card rounded-3xl shadow-card p-8 md:p-12 border border-white/5 relative overflow-hidden">

                <!-- Question 1 -->
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-500 delay-200"
                    x-transition:enter-start="opacity-0 translate-x-20"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-300 opacity-0 -translate-x-20" class="space-y-8">

                    <div class="space-y-2">
                        <span class="text-brand-neon text-xs font-bold tracking-widest uppercase">Etapa 1 de 3</span>
                        <h2 class="text-3xl font-bold text-white leading-tight">Qual será a missão principal da sua nova
                            Bike?</h2>
                    </div>

                    <div class="space-y-4">
                        <template x-for="option in questions[0].options" :key="option.label">
                            <button @click="selectOption(0, option)"
                                :class="answers.q1 === option.label ? 'border-brand-neon bg-brand-neon/10 shadow-glow' : 'border-white/10 hover:border-brand-neon/50 hover:bg-white/5'"
                                class="w-full text-left p-6 rounded-2xl border transition-all duration-300 flex justify-between items-center group relative overflow-hidden">
                                <span class="text-lg font-medium text-slate-200 group-hover:text-white relative z-10"
                                    x-text="option.label"></span>
                                <div class="w-6 h-6 rounded-full border-2 border-white/20 flex items-center justify-center transition-all duration-300 relative z-10"
                                    :class="answers.q1 === option.label ? 'border-brand-neon bg-brand-neon scale-110' : 'group-hover:border-brand-neon/50'">
                                    <svg x-show="answers.q1 === option.label" class="w-3.5 h-3.5 text-brand-base"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Question 2 -->
                <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-500 delay-200"
                    x-transition:enter-start="opacity-0 translate-x-20"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-300 opacity-0 -translate-x-20" class="space-y-8">

                    <div class="space-y-2">
                        <span class="text-brand-neon text-xs font-bold tracking-widest uppercase">Etapa 2 de 3</span>
                        <h2 class="text-3xl font-bold text-white leading-tight">Como é o trajeto que você pretende
                            fazer?</h2>
                    </div>

                    <div class="space-y-4">
                        <template x-for="option in questions[1].options" :key="option.label">
                            <button @click="selectOption(1, option)"
                                :class="answers.q2 === option.label ? 'border-brand-neon bg-brand-neon/10 shadow-glow' : 'border-white/10 hover:border-brand-neon/50 hover:bg-white/5'"
                                class="w-full text-left p-6 rounded-2xl border transition-all duration-300 flex justify-between items-center group relative overflow-hidden">
                                <span class="text-lg font-medium text-slate-200 group-hover:text-white relative z-10"
                                    x-text="option.label"></span>
                                <div class="w-6 h-6 rounded-full border-2 border-white/20 flex items-center justify-center transition-all duration-300 relative z-10"
                                    :class="answers.q2 === option.label ? 'border-brand-neon bg-brand-neon scale-110' : 'group-hover:border-brand-neon/50'">
                                    <svg x-show="answers.q2 === option.label" class="w-3.5 h-3.5 text-brand-base"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </button>
                        </template>
                    </div>

                    <div class="flex justify-start pt-2">
                        <button @click="step--"
                            class="text-slate-400 hover:text-white text-sm font-medium px-2 py-2 transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7"></path>
                            </svg> Voltar
                        </button>
                    </div>
                </div>

                <!-- Question 3 -->
                <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-500 delay-200"
                    x-transition:enter-start="opacity-0 translate-x-20"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-300 opacity-0 -translate-x-20" class="space-y-8">

                    <div class="space-y-2">
                        <span class="text-brand-neon text-xs font-bold tracking-widest uppercase">Etapa 3 de 3</span>
                        <h2 class="text-3xl font-bold text-white leading-tight">Quando você pretende começar a
                            economizar?</h2>
                    </div>

                    <div class="space-y-4">
                        <template x-for="option in questions[2].options" :key="option.label">
                            <button @click="selectOption(2, option)"
                                :class="answers.q3 === option.label ? 'border-brand-neon bg-brand-neon/10 shadow-glow' : 'border-white/10 hover:border-brand-neon/50 hover:bg-white/5'"
                                class="w-full text-left p-6 rounded-2xl border transition-all duration-300 flex justify-between items-center group relative overflow-hidden">
                                <span class="text-lg font-medium text-slate-200 group-hover:text-white relative z-10"
                                    x-text="option.label"></span>
                                <div class="w-6 h-6 rounded-full border-2 border-white/20 flex items-center justify-center transition-all duration-300 relative z-10"
                                    :class="answers.q3 === option.label ? 'border-brand-neon bg-brand-neon scale-110' : 'group-hover:border-brand-neon/50'">
                                    <svg x-show="answers.q3 === option.label" class="w-3.5 h-3.5 text-brand-base"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                            </button>
                        </template>
                    </div>

                    <div class="flex justify-start pt-2">
                        <button @click="step--"
                            class="text-slate-400 hover:text-white text-sm font-medium px-2 py-2 transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7"></path>
                            </svg> Voltar
                        </button>
                    </div>
                </div>

                <!-- Contact Form (Step 4) -->
                <div x-show="step === 4" x-cloak x-transition:enter="transition ease-out duration-500 delay-200"
                    x-transition:enter-start="opacity-0 translate-y-10"
                    x-transition:enter-end="opacity-100 translate-y-0" class="space-y-8">

                    <div class="text-center space-y-2">
                        <h2 class="text-3xl font-bold text-white">Configuração Pronta!</h2>
                        <p class="text-slate-300">Insira seu WhatsApp abaixo para receber a Ficha Técnica da sua bike
                            ideal e o Código do seu Bônus.</p>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-5 relative z-30">
                        <div class="space-y-4">
                            <div class="group">
                                <label class="block text-xs font-bold text-brand-neon uppercase mb-1 ml-1">Seu
                                    Nome</label>
                                <!-- FIX: Added relative z-30 to inputs -->
                                <input type="text" x-model="contact.nome" required
                                    class="relative z-30 w-full p-4 bg-brand-base/50 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-brand-neon focus:ring-1 focus:ring-brand-neon outline-none transition-all cursor-text"
                                    placeholder="Digite seu nome completo">
                            </div>
                            <div class="group">
                                <label
                                    class="block text-xs font-bold text-brand-neon uppercase mb-1 ml-1">WhatsApp</label>
                                <input type="tel" x-model="contact.telefone" required
                                    class="relative z-30 w-full p-4 bg-brand-base/50 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-brand-neon focus:ring-1 focus:ring-brand-neon outline-none transition-all cursor-text"
                                    placeholder="(11) 99999-9999">
                            </div>
                            <div class="group">
                                <label class="block text-xs font-bold text-brand-neon uppercase mb-1 ml-1">Email
                                    (Opcional)</label>
                                <input type="email" x-model="contact.email"
                                    class="relative z-30 w-full p-4 bg-brand-base/50 border border-white/10 rounded-xl text-white placeholder-slate-500 focus:border-brand-neon focus:ring-1 focus:ring-brand-neon outline-none transition-all cursor-text"
                                    placeholder="seu@email.com">
                            </div>
                        </div>

                        <div class="pt-4">
                            <button type="submit" :disabled="isSubmitting"
                                class="relative z-30 w-full bg-brand-neon hover:bg-white text-brand-base font-bold py-4 rounded-xl shadow-lg hover:shadow-neon transition-all transform hover:-translate-y-0.5 flex justify-center items-center cursor-pointer">
                                <span x-show="!isSubmitting" class="text-lg tracking-wide">VER MEU RESULTADO</span>
                                <svg x-show="isSubmitting" class="animate-spin h-6 w-6 text-brand-base"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Success Screen (Step 5) - Intelligent Routing -->
                <div x-show="step === 5" x-cloak x-transition:enter="transition ease-out duration-700"
                    x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                    class="text-center space-y-8 py-4">

                    <!-- Scenario A: HOT (Score >= 30) -->
                    <div x-show="totalScore >= 30" class="space-y-6">
                        <div class="relative w-24 h-24 mx-auto">
                            <div class="absolute inset-0 bg-brand-neon/20 rounded-full blur-xl animate-pulse"></div>
                            <div
                                class="relative w-full h-full bg-gradient-to-br from-brand-neon to-brand-accent rounded-full flex items-center justify-center shadow-glow">
                                <svg class="w-12 h-12 text-brand-base" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h2 class="text-2xl md:text-3xl font-bold text-white leading-tight">RESULTADO: A Bike Ideal
                                para você é a <span class="text-brand-neon">Lyam X-Pro</span></h2>
                            <p
                                class="text-lg text-white/90 bg-brand-neon/10 border border-brand-neon/20 p-4 rounded-xl">
                                🔥 <span class="font-bold text-brand-neon">Ótima notícia:</span> Você foi APROVADO para
                                o Bônus de Kit Acessórios (R$ 450,00). <br /><span class="text-sm opacity-80">Mas
                                    atenção: Temos apenas 3 unidades dessa configuração em estoque.</span>
                            </p>
                        </div>

                        <div
                            class="bg-brand-base/50 border border-white/10 p-6 rounded-2xl backdrop-blur-sm text-left space-y-3">
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-brand-neon flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="text-slate-200"><strong class="text-white">Motor:</strong> Ideal para suas
                                    subidas.</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-brand-neon flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 7m0 13V7">
                                    </path>
                                </svg>
                                <span class="text-slate-200"><strong class="text-white">Autonomia:</strong> Cobre sua
                                    rota com folga.</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <svg class="w-5 h-5 text-brand-neon flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-slate-200"><strong class="text-white">Ação:</strong> Reserve agora
                                    para garantir o Bônus.</span>
                            </div>
                        </div>

                        <a href="https://api.whatsapp.com/send?phone=5511987654321&text=Ol%C3%A1%2C%20fiz%20o%20quiz%20e%20deu%20a%20bike%20X.%20Quero%20garantir%20meu%20b%C3%B4nus%20de%20acess%C3%B3rios.%20Tenho%20urg%C3%AAncia."
                            target="_blank"
                            class="w-full bg-brand-neon hover:bg-white text-brand-base font-bold py-4 rounded-xl shadow-lg hover:shadow-neon transition-all transform hover:-translate-y-0.5 flex justify-center items-center animate-pulse-slow text-center leading-tight px-4">
                            👉 FALAR COM ESPECIALISTA E RESERVAR BÔNUS
                        </a>
                    </div>

                    <!-- Scenario B: WARM (Score 15-29) -->
                    <div x-show="totalScore >= 15 && totalScore < 30" class="space-y-6">
                        <div class="space-y-4">
                            <h2 class="text-2xl md:text-3xl font-bold text-white leading-tight">Sua recomendação: <span
                                    class="text-brand-neon">Lyam City Comfort</span></h2>
                        </div>

                        <!-- VSL Placeholder -->
                        <div
                            class="w-full aspect-video bg-brand-base border border-white/10 rounded-2xl flex items-center justify-center relative overflow-hidden group cursor-pointer">
                            <div
                                class="absolute inset-0 bg-brand-neon/5 group-hover:bg-brand-neon/10 transition-colors">
                            </div>
                            <svg class="w-16 h-16 text-brand-neon opacity-80 group-hover:scale-110 transition-transform duration-300"
                                fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z">
                                </path>
                            </svg>
                            <span class="absolute bottom-4 text-xs text-slate-400 uppercase tracking-widest">Vídeo de
                                Apresentação</span>
                        </div>

                        <p class="text-slate-300 text-sm md:text-base">
                            "Essa máquina foi projetada para rotinas como a sua. Assista ao vídeo acima para entender
                            por que ela é a líder de vendas."
                        </p>

                        <a href="https://api.whatsapp.com/send?phone=5511999999999&text=Oi%2C%20vi%20o%20v%C3%ADdeo%20da%20bike%20Y%20e%20quero%20saber%20sobre%20parcelamento."
                            target="_blank"
                            class="w-full bg-brand-neon hover:bg-white text-brand-base font-bold py-4 rounded-xl shadow-lg hover:shadow-neon transition-all transform hover:-translate-y-0.5 flex justify-center items-center text-center px-4">
                            👉 TIRAR DÚVIDAS E SIMULAR PARCELAS
                        </a>
                    </div>

                    <!-- Scenario C: COLD (Score <= 14) -->
                    <div x-show="totalScore < 15" class="space-y-8">
                        <div class="space-y-4">
                            <h2 class="text-2xl md:text-3xl font-bold text-white leading-tight">Obrigado! Aqui está
                                nosso Catálogo Completo.</h2>
                            <p class="text-slate-300">
                                Enviamos para seu e-mail um Guia de como escolher sua primeira bike elétrica.
                            </p>
                        </div>

                        <div class="py-4">
                            <svg class="w-20 h-20 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                </path>
                            </svg>
                        </div>

                        <a href="https://www.instagram.com/bikeleads_catalogo" target="_blank"
                            class="w-full border-2 border-brand-neon text-brand-neon hover:bg-brand-neon hover:text-brand-base font-bold py-4 rounded-xl transition-all transform hover:-translate-y-0.5 flex justify-center items-center text-center px-4">
                            BAIXAR CATÁLOGO E SEGUIR NO INSTAGRAM
                        </a>
                    </div>

                </div>

            </div>
        </div>

        <!-- Processing Screen -->
        <div x-show="step === 'processing'" x-cloak x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-300 opacity-0"
            class="absolute inset-0 z-50 bg-brand-base flex flex-col items-center justify-center text-center px-6">

            <div class="relative w-20 h-20 mb-8">
                <div class="absolute inset-0 border-4 border-brand-card rounded-full"></div>
                <div class="absolute inset-0 border-4 border-brand-neon rounded-full border-t-transparent animate-spin">
                </div>
            </div>

            <h2 class="text-2xl md:text-3xl font-bold text-white mb-2 transition-all duration-300"
                x-text="processingMessage"></h2>
            <p class="text-brand-neon animate-pulse">Aguarde um momento...</p>
        </div>

    </main>

    <!-- Footer -->
    <footer
        class="fixed bottom-4 w-full text-center text-slate-500/30 text-[10px] z-0 pointer-events-none uppercase tracking-widest">
        Lyam Bikes &copy; 2024
    </footer>

    <script>
        function quizApp() {
            return {
                step: 0,
                progress: 10,
                isSubmitting: false,
                processingMessage: '',
                questions: [
                    {
                        id: 'q1',
                        text: "Qual será a missão principal da sua nova Bike Elétrica?",
                        options: [
                            { label: "Fugir do trânsito / Ir para o Trabalho diariamente.", score: 10 },
                            { label: "Fazer Renda Extra / Entregas de App.", score: 10 },
                            { label: "Uso Misto (Lazer no FDS e padaria na semana).", score: 5 },
                            { label: "Apenas Lazer eventual / Passeio.", score: 0 }
                        ]
                    },
                    {
                        id: 'q2',
                        text: "Como é o trajeto que você pretende fazer?",
                        options: [
                            { label: "Muitas subidas íngremes e distância longa (+20km).", score: 10 },
                            { label: "Trajeto plano ou subidas leves/médias.", score: 5 },
                            { label: "Apenas dentro do bairro / Trajeto curto.", score: 0 }
                        ]
                    },
                    {
                        id: 'q3',
                        text: "Quando você pretende começar a economizar?",
                        options: [
                            { label: "Imediatamente. Preciso para ontem.", score: 20 },
                            { label: "Nos próximos 30 dias.", score: 10 },
                            { label: "Estou apenas pesquisando para o futuro.", score: 0 }
                        ]
                    }
                ],
                answers: {
                    q1: null,
                    q2: null,
                    q3: null
                },
                scores: {
                    q1: 0,
                    q2: 0,
                    q3: 0
                },
                contact: {
                    nome: '',
                    email: '',
                    telefone: '',
                    instagram: ''
                },
                get totalScore() {
                    return this.scores.q1 + this.scores.q2 + this.scores.q3;
                },
                selectOption(questionIndex, option) {
                    const qId = this.questions[questionIndex].id;
                    this.answers[qId] = option.label;
                    this.scores[qId] = option.score;

                    setTimeout(() => {
                        // Check if it's the last question (index 2)
                        if (questionIndex === this.questions.length - 1) {
                            this.startProcessing();
                        } else {
                            this.nextStep();
                        }
                    }, 350);
                },
                nextStep() {
                    this.step++;
                    this.updateProgress();
                },
                startProcessing() {
                    this.step = 'processing';
                    this.progress = 85;

                    // Sequence
                    this.processingMessage = '🔄 Analisando topografia do trajeto...';

                    setTimeout(() => {
                        this.processingMessage = '✅ Calculando potência de motor ideal...';
                    }, 1000);

                    setTimeout(() => {
                        this.processingMessage = '🎁 Verificando disponibilidade de Bônus...';
                    }, 2000);

                    setTimeout(() => {
                        this.step = 4; // Go to Contact
                        this.updateProgress();
                    }, 3000);
                },
                updateProgress() {
                    if (this.step === 0) this.progress = 10;
                    else if (this.step === 1) this.progress = 35;
                    else if (this.step === 2) this.progress = 60;
                    else if (this.step === 3) this.progress = 85; // Should not happen due to processing
                    else if (this.step === 4) this.progress = 90;
                    else this.progress = 100;
                },
                async submitForm() {
                    this.isSubmitting = true;

                    const payload = {
                        ...this.contact,
                        q1_missao: this.answers.q1,
                        q2_trajeto: this.answers.q2,
                        q3_prazo: this.answers.q3,
                        score_total: this.totalScore
                    };

                    try {
                        // FIX: Use relative path for API
                        const response = await fetch('api/new-lead.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (result.success) {
                            this.step = 5;
                            this.updateProgress();
                        } else {
                            alert('Erro ao enviar: ' + (result.message || 'Tente novamente.'));
                        }
                    } catch (error) {
                        console.error('Erro:', error);
                        alert('Ocorreu um erro ao enviar os dados. Verifique sua conexão.');
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }
        }
    </script>
</body>

</html>