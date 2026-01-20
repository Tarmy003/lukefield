<div class="topbar d-print-none">
    <div class="container-xxl">
        <nav class="topbar-custom d-flex justify-content-between" id="topbar-custom">

            <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0">
                <li>
                    <button class="nav-link mobile-menu-btn nav-icon" id="togglemenu">
                        <i class="iconoir-menu-scale"></i>
                    </button>
                </li>
                <li class="mx-3 welcome-text">
                    <h3 class="mb-0 fw-bold text-truncate" id="greetingText">Good day!</h3>
                </li>
                
            </ul>

            @auth
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        const greetingText = document.getElementById("greetingText");
                        const userName = "{{ Auth::user()->name }}";

                        const now = new Date();
                        const hour = now.getHours();
                        const dayOfWeek = now.getDay(); // 0 = Sunday, 1 = Monday, etc.
                        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

                        // Time-based greetings
                        let timeGreeting;
                        if (hour >= 5 && hour < 12) {
                            timeGreeting = "Good Morning";
                        } else if (hour >= 12 && hour < 17) {
                            timeGreeting = "Good Afternoon";
                        } else if (hour >= 17 && hour < 21) {
                            timeGreeting = "Good Evening";
                        } else {
                            timeGreeting = "Good Night";
                        }

                        // Jokes collection
                        const jokes = [
                            "Why don't scientists trust atoms? Because they make up everything!",
                            "I told my wife she was drawing her eyebrows too high. She looked surprised!",
                            "Why did the coffee file a police report? It got mugged!",
                            "What do you call a fake noodle? An impasta!",
                            "Why don't eggs tell jokes? They'd crack each other up!",
                            "I used to hate facial hair, but then it grew on me!",
                            "What do you call a bear with no teeth? A gummy bear!",
                            "Why did the math book look so sad? Because it had too many problems!",
                            "What's the best thing about Switzerland? I don't know, but the flag is a big plus!",
                            "Why don't some couples go to the gym? Because some relationships don't work out!",
                            "Why did the developer go broke? Because he used up all his cache!",
                            "What's a computer's favorite snack? Microchips!",
                            "Why do programmers prefer dark mode? Because light attracts bugs!",
                            "How many tickles does it take to make an octopus laugh? Ten-tickles!",
                            "Why don't skeletons fight each other? They don't have the guts!",
                            "What did the ocean say to the beach? Nothing, it just waved!",
                            "Why did the scarecrow win an award? He was outstanding in his field!",
                            "What do you call a sleeping bull? A bulldozer!",
                            "Why don't scientists trust stairs? Because they're always up to something!",
                            "What's orange and sounds like a parrot? A carrot!"
                        ];

                        // General Tips collection
                        const tips = [
                            "💡 Tip: Take a 5-minute break every hour to boost your productivity!",
                            "💡 Tip: Start your day by writing down 3 things you're grateful for.",
                            "💡 Tip: Use the 2-minute rule - if it takes less than 2 minutes, do it now!",
                            "💡 Tip: Keep a water bottle at your desk to stay hydrated throughout the day.",
                            "💡 Tip: Organize your workspace at the end of each day for a fresh start tomorrow.",
                            "💡 Tip: Practice the 20-20-20 rule: every 20 minutes, look at something 20 feet away for 20 seconds.",
                            "💡 Tip: Batch similar tasks together to minimize context switching.",
                            "💡 Tip: Set boundaries with your time - it's okay to say no to non-essential meetings.",
                            "💡 Tip: Take notes by hand when possible - it helps with memory retention!",
                            "💡 Tip: Create templates for repetitive tasks to save time and ensure consistency.",
                            "💡 Tip: Use the Pomodoro Technique - 25 minutes focused work, 5-minute break.",
                            "💡 Tip: Keep your phone in another room to minimize distractions.",
                            "💡 Tip: Practice inbox zero - process emails immediately: do, delegate, defer, or delete.",
                            "💡 Tip: Set up automated workflows for routine tasks.",
                            "💡 Tip: Use the 'Swiss cheese' method - poke holes in big projects by doing small parts.",
                            "💡 Tip: Create a 'done' list alongside your 'to-do' list for motivation."
                        ];

                        // Keyboard Shortcuts Tips
                        const keyboardShortcuts = [
                            "⌨️ Shortcut: Ctrl+Shift+T reopens your last closed browser tab - lifesaver!",
                            "⌨️ Shortcut: Alt+Tab to quickly switch between applications.",
                            "⌨️ Shortcut: Ctrl+L instantly jumps to the address bar in any browser.",
                            "⌨️ Shortcut: Win+D minimizes all windows and shows desktop instantly.",
                            "⌨️ Shortcut: Ctrl+Shift+N opens incognito/private browsing window.",
                            "⌨️ Shortcut: F2 renames files and folders instantly - no right-clicking needed!",
                            "⌨️ Shortcut: Ctrl+Shift+V pastes text without formatting.",
                            "⌨️ Shortcut: Win+L locks your computer instantly for security.",
                            "⌨️ Shortcut: Ctrl+K in most apps opens the search function.",
                            "⌨️ Shortcut: Alt+F4 closes the current application window.",
                            "⌨️ Shortcut: Ctrl+Shift+Esc opens Task Manager directly.",
                            "⌨️ Shortcut: Win+V opens clipboard history in Windows 10/11.",
                            "⌨️ Shortcut: Ctrl+` switches between multiple windows of the same app.",
                            "⌨️ Shortcut: Win+Shift+S opens screenshot tool for selective captures.",
                            "⌨️ Shortcut: Ctrl+W closes current tab instead of entire browser."
                        ];

                        // Work Motivational quotes
                        const workMotivations = [
                            "🏢 Your dedication at Lukefield makes a real difference - every task matters!",
                            "⚡ Excellence isn't a skill, it's an attitude. You're bringing that energy!",
                            "🎯 Great work happens when preparation meets opportunity - you're ready!",
                            "🚀 Your innovative thinking is exactly what drives Lukefield forward!",
                            "💼 Professional growth happens one challenge at a time - embrace each one!",
                            "🏆 Quality work is never an accident - it's the result of effort, you're proving that!",
                            "⭐ Your problem-solving skills are making complex things simple for everyone!",
                            "🔥 Every project you complete builds your expertise and our company's success!",
                            "💡 Your creative solutions today become tomorrow's best practices!",
                            "🌟 Leadership isn't about position - it's about influence. You're leading by example!",
                            "🎪 Teamwork makes the dream work, and you're an essential part of our dream team!",
                            "📈 Your consistent performance is the foundation of Lukefield's growth!",
                            "💪 Challenges are just opportunities wearing work clothes - you're dressed for success!",
                            "🏅 Your attention to detail doesn't go unnoticed - it's what sets us apart!",
                            "⚡ Productivity isn't about being busy - it's about being effective. You've mastered that!"
                        ];

                        // Life Motivational quotes
                        const lifeMotivations = [
                            "🌅 Every sunrise is a new chance to rewrite your story!",
                            "🌱 Growth begins at the end of your comfort zone - step boldly!",
                            "💫 Your dreams don't have expiration dates - it's never too late!",
                            "🦋 Change is the only constant - embrace it and transform!",
                            "🌈 After every storm comes a rainbow - keep weathering through!",
                            "🏔️ Mountains aren't climbed in giant leaps but one step at a time!",
                            "⭐ You are the author of your own life story - make it inspiring!",
                            "🌊 Life is like surfing - you can't control the waves, but you can learn to ride them!",
                            "🔮 The future belongs to those who believe in the beauty of their dreams!",
                            "🎭 Life isn't about finding yourself - it's about creating yourself!",
                            "🌟 Your potential is like a seed - it needs belief and persistence to bloom!",
                            "🚪 When one door closes, another opens - but sometimes we stare too long at the closed door!",
                            "🎨 Life is your canvas - paint it with bold, beautiful strokes!",
                            "🏃‍♂️ Life is a marathon, not a sprint - pace yourself but keep moving!",
                            "💎 Pressure and time create diamonds - you're becoming more valuable every day!",
                            "🌸 Bloom where you're planted while working toward where you want to grow!",
                            "🎯 Success is not about the destination - it's about who you become on the journey!"
                        ];

                        // Health & Wellness Benefits
                        const healthTips = [
                            "🧘‍♀️ Health Tip: 5 deep breaths can lower your stress hormones instantly!",
                            "🚶‍♂️ Health Tip: A 10-minute walk can boost creativity by up to 60%!",
                            "💧 Health Tip: Drinking water first thing in the morning kickstarts your metabolism!",
                            "😴 Health Tip: Quality sleep improves memory consolidation by 40%!",
                            "🥗 Health Tip: Eating colorful foods ensures you get diverse nutrients!",
                            "🧠 Health Tip: Meditation for just 10 minutes daily can improve focus and reduce anxiety!",
                            "☀️ Health Tip: 15 minutes of sunlight daily helps maintain healthy Vitamin D levels!",
                            "💪 Health Tip: Standing and stretching every hour improves circulation and energy!",
                            "🫖 Health Tip: Green tea contains L-theanine which promotes calm alertness!",
                            "😊 Health Tip: Laughing releases endorphins - your natural mood boosters!",
                            "🎵 Health Tip: Listening to music can reduce cortisol levels and boost immunity!",
                            "🌿 Health Tip: Indoor plants can improve air quality and reduce stress by 15%!",
                            "🤝 Health Tip: Social connections are as important for longevity as exercise!",
                            "📱 Health Tip: Blue light filters after sunset help maintain natural sleep cycles!",
                            "🧘 Health Tip: Progressive muscle relaxation can reduce physical tension in minutes!",
                            "🍎 Health Tip: Eating an apple can be more effective than caffeine for staying alert!"
                        ];

                        // Lukefield Appreciation Messages
                        const lukefieldMessages = [
                            "🏢 Lukefield Family: Your dedication doesn't go unnoticed - you're valued every single day!",
                            "💙 At Lukefield, we don't just work together - we grow together. Thank you for being amazing!",
                            "🌟 Lukefield Success Story: Every achievement starts with team members like you!",
                            "🤝 Lukefield Community: Your contributions make this workplace feel like home!",
                            "🏆 Lukefield Pride: You're not just an employee - you're an integral part of our success story!",
                            "💫 From all of us at Lukefield: Your hard work and positive attitude inspire everyone around you!",
                            "🎯 Lukefield Vision: With team members like you, our future looks incredibly bright!",
                            "❤️ Lukefield Gratitude: Thank you for bringing your best self to work every day!",
                            "🚀 Lukefield Innovation: Your creative thinking drives us toward excellence!",
                            "🌈 Lukefield Culture: You help make this workplace a positive, productive environment for everyone!",
                            "⭐ Lukefield Recognition: Your professionalism and dedication set the standard for excellence!",
                            "🎪 Lukefield Team Spirit: Together we're stronger, and you're a vital part of that strength!",
                            "💪 Lukefield Resilience: Thank you for adapting, growing, and thriving with us!",
                            "🏅 Lukefield Excellence: Your commitment to quality reflects the best of our company values!",
                            "🌟 Lukefield Impact: Every task you complete contributes to something bigger - thank you!",
                            "🔥 Lukefield Energy: Your enthusiasm is contagious and makes everyone's day better!"
                        ];

                        // Energy Boosters
                        const energyBoosters = [
                            "⚡ Energy Boost: Take 3 deep breaths and stretch your arms above your head!",
                            "🎵 Energy Boost: Play your favorite upbeat song - music instantly lifts your mood!",
                            "💧 Energy Boost: Dehydration causes fatigue - drink a full glass of water right now!",
                            "🌞 Energy Boost: Look out a window or step outside for 2 minutes - natural light energizes!",
                            "🤸‍♂️ Energy Boost: Do 10 jumping jacks - get that blood flowing!",
                            "😄 Energy Boost: Smile for 30 seconds - it tricks your brain into feeling happier!",
                            "🍊 Energy Boost: The scent of citrus can increase alertness by 54%!",
                            "🧘‍♂️ Energy Boost: Roll your shoulders back and sit up tall - posture affects energy!",
                            "❄️ Energy Boost: Splash cold water on your wrists - instant refresh!",
                            "🗣️ Energy Boost: Call or text someone you care about - connection energizes!",
                            "🎯 Energy Boost: Write down one thing you're excited about today!",
                            "🌱 Energy Boost: Look at something green - it's naturally refreshing for your eyes!",
                            "🏃‍♂️ Energy Boost: Walk to the furthest water fountain or restroom - movement matters!",
                            "🧠 Energy Boost: Do a quick brain teaser or puzzle - mental stimulation wakes you up!",
                            "🌟 Energy Boost: Think of your biggest recent accomplishment - pride energizes!"
                        ];

                        // Day-specific messages
                        const dayMessages = {
                            0: { // Sunday
                                messages: [
                                    "🌅 Sunday vibes! Time to recharge and prepare for an amazing week ahead!",
                                    "📚 Perfect day for planning and reflection. What are your goals for this week?",
                                    "🧘‍♂️ Take it easy today - rest is productive too!",
                                    "🎯 Sunday planning leads to Monday success!"
                                ]
                            },
                            1: { // Monday
                                messages: [
                                    "💪 Monday Motivation! Fresh start, fresh opportunities!",
                                    "🚀 New week, new possibilities! Let's make it count!",
                                    "☀️ Monday is your chance to set the tone for the entire week!",
                                    "⚡ Fuel up that Monday energy - you've got this!"
                                ]
                            },
                            2: { // Tuesday
                                messages: [
                                    "🔥 Tuesday Momentum! Keep that energy flowing!",
                                    "⚡ Tuesday is for tackling those important tasks!",
                                    "🎯 You're in the groove now - maintain that rhythm!",
                                    "💯 Tuesday productivity mode: ON!"
                                ]
                            },
                            3: { // Wednesday
                                messages: [
                                    "🐪 Hump Day Champion! You're halfway through - keep pushing!",
                                    "⚡ Wednesday wisdom: Consistency beats perfection!",
                                    "⚡ Wednesday wisdom: Do not forget to check out Funmilayo's Wellness article on our public notices",
                                    "🎢 Mid-week checkpoint - you're doing amazing!",
                                    "💪 Wednesday warrior mode activated!"
                                ]
                            },
                            4: { // Thursday
                                messages: [
                                    "🚀 Thursday Thrive! Almost at the finish line!",
                                    "✨ Thursday energy: Strong finish incoming!",
                                    "🎯 Thursday focus: Prepare for Friday success!",
                                    "💪 You're so close to weekend victory!"
                                ]
                            },
                            5: { // Friday
                                messages: [
                                    "🎉 Friday Feeling! You made it through another awesome week!",
                                    "🏁 Friday finish line! Time to celebrate your wins!",
                                    "✨ TGIF! Reflect on this week's achievements!",
                                    "🎊 Friday success - you earned that weekend!"
                                ]
                            },
                            6: { // Saturday
                                messages: [
                                    "🎈 Saturday Special! Time for fun and relaxation!",
                                    "🌟 Weekend warrior! Balance work and play today!",
                                    "🎨 Saturday creativity - try something new today!",
                                    "☀️ Saturday sunshine - make the most of your free time!"
                                ]
                            }
                        };

                        // Fun facts
                        const funFacts = [
                            "🤓 Fun fact: Honey never spoils! Archaeologists have found edible honey in ancient tombs.",
                            "🐙 Fun fact: Octopuses have three hearts and blue blood!",
                            "🌍 Fun fact: A group of flamingos is called a 'flamboyance'!",
                            "🦆 Fun fact: Rubber ducks help debug code better than most colleagues!",
                            "🍌 Fun fact: Bananas are berries, but strawberries aren't!",
                            "🐝 Fun fact: A bee visits about 2 million flowers to make one pound of honey!",
                            "🌙 Fun fact: The moon moves about 1.5 inches away from Earth each year!",
                            "🦈 Fun fact: Sharks have been around longer than trees!",
                            "🐧 Fun fact: Penguins propose to their mates with pebbles!",
                            "🎵 Fun fact: Music can boost your productivity by up to 15%!",
                            "🧠 Fun fact: Your brain uses 20% of your body's total energy!",
                            "🦒 Fun fact: Giraffes only sleep 2-6 hours per day!",
                            "🍯 Fun fact: It takes 556 worker bees to collect 1 pound of honey!",
                            "🌊 Fun fact: The Pacific Ocean contains more than half of the free water on Earth!",
                            "⚡ Fun fact: Lightning strikes the Earth about 100 times per second!",
                            "🐨 Fun fact: Koalas sleep up to 22 hours a day!",
                            "🎭 Fun fact: Laughing lowers levels of stress hormones!",
                            "🌟 Fun fact: There are more possible games of chess than atoms in the observable universe!",
                            "🦋 Fun fact: Butterflies taste with their feet!",
                            "🐘 Fun fact: Elephants are one of the few animals that can recognize themselves in a mirror!"
                        ];

                        // Career & Professional Development
                        const careerTips = [
                            "📈 Career Tip: Network within your industry - 80% of jobs are never advertised!",
                            "🎓 Career Tip: Learn something new every month to stay competitive!",
                            "📊 Career Tip: Document your achievements - you'll need them for reviews and interviews!",
                            "🤝 Career Tip: Be the person who helps others succeed - it comes back tenfold!",
                            "💼 Career Tip: Seek feedback regularly - it's the fastest way to improve!",
                            "🎯 Career Tip: Set SMART goals - Specific, Measurable, Achievable, Relevant, Time-bound!",
                            "📚 Career Tip: Read industry publications to stay ahead of trends!",
                            "🗣️ Career Tip: Practice your elevator pitch - you never know when you'll need it!",
                            "🌐 Career Tip: Build your personal brand online - future opportunities depend on it!",
                            "🏆 Career Tip: Celebrate small wins - they build momentum for bigger achievements!"
                        ];

                        // Workplace Wisdom
                        const workplaceWisdom = [
                            "🏢 Workplace Wisdom: Under-promise and over-deliver - it builds trust!",
                            "🤔 Workplace Wisdom: Ask 'What would success look like?' before starting any project!",
                            "📧 Workplace Wisdom: Reply within 24 hours, even if just to acknowledge receipt!",
                            "🎪 Workplace Wisdom: Make others look good, and you'll look good too!",
                            "🔍 Workplace Wisdom: Focus on solutions, not problems - be known as a problem-solver!",
                            "⏰ Workplace Wisdom: Arrive 10 minutes early - it shows respect and professionalism!",
                            "📝 Workplace Wisdom: Take notes in meetings - it shows you're engaged and prepared!",
                            "🎨 Workplace Wisdom: Creativity thrives within constraints - embrace limitations!",
                            "🔄 Workplace Wisdom: Adapt quickly to change - flexibility is a superpower!",
                            "🏅 Workplace Wisdom: Excellence is a habit, not an act - practice it daily!"
                        ];

                        // Weather-themed messages (you can enhance with real weather data)
                        const weatherMessages = [
                            "☀️ Sunny disposition leads to productive days!",
                            "🌧️ Rainy days are perfect for focused indoor work!",
                            "❄️ Cool weather, hot productivity!",
                            "🌤️ Partly cloudy, fully motivated!",
                            "⛈️ Weather the storms, celebrate the rainbows!"
                        ];

                        function getRandomItem(array) {
                            return array[Math.floor(Math.random() * array.length)];
                        }

                        function createInteractiveGreeting() {
                            const currentDay = dayNames[dayOfWeek];

                            // Base greeting with time and day
                            let greeting = `${timeGreeting}, ${userName}! Happy ${currentDay}!`;

                            // Add different types of content based on random selection
                            const contentTypes = [
                                'dayMessage', 'joke', 'tip', 'keyboardShortcut', 'workMotivation',
                                'lifeMotivation', 'healthTip', 'lukefieldMessage', 'energyBooster',
                                'funFact', 'weather', 'careerTip', 'workplaceWisdom'
                            ];
                            const selectedType = getRandomItem(contentTypes);

                            let additionalContent = "";

                            switch (selectedType) {
                                case 'dayMessage':
                                    additionalContent = getRandomItem(dayMessages[dayOfWeek].messages);
                                    break;
                                case 'joke':
                                    additionalContent = "😄 " + getRandomItem(jokes);
                                    break;
                                case 'tip':
                                    additionalContent = getRandomItem(tips);
                                    break;
                                case 'keyboardShortcut':
                                    additionalContent = getRandomItem(keyboardShortcuts);
                                    break;
                                case 'workMotivation':
                                    additionalContent = getRandomItem(workMotivations);
                                    break;
                                case 'lifeMotivation':
                                    additionalContent = getRandomItem(lifeMotivations);
                                    break;
                                case 'healthTip':
                                    additionalContent = getRandomItem(healthTips);
                                    break;
                                case 'lukefieldMessage':
                                    additionalContent = getRandomItem(lukefieldMessages);
                                    break;
                                case 'energyBooster':
                                    additionalContent = getRandomItem(energyBoosters);
                                    break;
                                case 'funFact':
                                    additionalContent = getRandomItem(funFacts);
                                    break;
                                case 'weather':
                                    additionalContent = getRandomItem(weatherMessages);
                                    break;
                                case 'careerTip':
                                    additionalContent = getRandomItem(careerTips);
                                    break;
                                case 'workplaceWisdom':
                                    additionalContent = getRandomItem(workplaceWisdom);
                                    break;
                            }

                            return { greeting, additionalContent, currentDay };
                        }

                        // Create the interactive greeting
                        const greetingData = createInteractiveGreeting();

                        // Apply the greeting with animation
                        greetingText.innerHTML = `
            <div class="greeting-main">${greetingData.greeting}</div>
            <div class="greeting-extra" style="font-size: 0.7em; font-weight: 400; margin-top: 4px; opacity: 0.9; line-height: 1.3;">
                ${greetingData.additionalContent}
            </div>
        `;

                        // Add some CSS for better styling
                        if (!document.getElementById('greeting-styles')) {
                            const style = document.createElement('style');
                            style.id = 'greeting-styles';
                            style.textContent = `
                .greeting-main {
                    animation: fadeInDown 0.6s ease-out;
                }
                .greeting-extra {
                    animation: fadeInUp 0.8s ease-out 0.3s both;
                    max-width: 300px;
                    text-align: left;
                }
                @keyframes fadeInDown {
                    from { opacity: 0; transform: translateY(-10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .greeting-main:hover ~ .greeting-extra,
                .greeting-extra:hover {
                    opacity: 1 !important;
                    transform: scale(1.02);
                    transition: all 0.3s ease;
                }
            `;
                            document.head.appendChild(style);
                        }

                        // Optional: Refresh greeting every 30 minutes
                        setInterval(() => {
                            const newGreetingData = createInteractiveGreeting();
                            greetingText.innerHTML = `
                <div class="greeting-main">${newGreetingData.greeting}</div>
                <div class="greeting-extra" style="font-size: 0.7em; font-weight: 400; margin-top: 4px; opacity: 0.9; line-height: 1.3;">
                    ${newGreetingData.additionalContent}
                </div>
            `;
                        }, 30 * 60 * 1000); // 30 minutes

                        // Add click interaction for manual refresh
                        greetingText.style.cursor = 'pointer';
                        greetingText.title = 'Click for a new greeting!';
                        greetingText.addEventListener('click', () => {
                            const newGreetingData = createInteractiveGreeting();
                            greetingText.innerHTML = `
                <div class="greeting-main">${newGreetingData.greeting}</div>
                <div class="greeting-extra" style="font-size: 0.7em; font-weight: 400; margin-top: 4px; opacity: 0.9; line-height: 1.3;">
                    ${newGreetingData.additionalContent}
                </div>
            `;
                        });
                    });
                </script>
            @else
                <script>
                    document.addEventListener("DOMContentLoaded", function () {
                        // Redirect to login if not authenticated
                        window.location.href = "{{ route('login') }}";
                    });
                </script>
            @endauth

            <ul class="topbar-item list-unstyled d-inline-flex align-items-center mb-0">
                <div class="container">
                    <img src="{{asset('backend/lukefield-logo-removebg-preview.png')}}" width="175px;">
                </div>



                @auth
                    <li class="dropdown topbar-item">
                        <a class="nav-link dropdown-toggle arrow-none nav-icon" data-bs-toggle="dropdown" href="#"
                            role="button" aria-haspopup="false" aria-expanded="false">
                            <img src="{{ asset('backend/default-avatar.jpg') }}" alt="" class="thumb-lg rounded-circle">
                        </a>
                        <div class="dropdown-menu dropdown-menu-end py-0">
                            <div class="d-flex align-items-center dropdown-item py-2 bg-secondary-subtle">
                                <div class="flex-shrink-0">
                                    <img src="{{ asset('backend/default-avatar.jpg') }}" alt=""
                                        class="thumb-md rounded-circle">
                                </div>
                                <div class="flex-grow-1 ms-2 text-truncate align-self-center">
                                    <h6 class="my-0 fw-medium text-dark fs-13">{{ auth()->user()->name }}</h6>
                                    <small class="text-muted mb-0">{{ auth()->user()->position }}</small>
                                </div>
                            </div>
                            <div class="dropdown-divider mt-0"></div>
                            <small class="text-muted px-2 pb-1 d-block">Account</small>
                            <a class="dropdown-item" href="#"><i
                                    class="las la-user fs-18 me-1 align-text-bottom"></i> Profile</a>
                            <a class="dropdown-item text-danger" href="{{ route('logout') }}"><i
                                    class="las la-power-off fs-18 me-1 align-text-bottom"></i> Logout</a>
                        </div>
                    </li>
                @else
                    <li class="topbar-item">
                        <a class="nav-link" href="{{ route('login') }}">Login</a>
                    </li>
                @endauth
            </ul>
        </nav>
    </div>
</div>

