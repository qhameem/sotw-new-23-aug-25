<!-- <a href="{{ route('products.create') }}"
   class="bg-white hover:bg-rose-50 text-primary-500 border border-primary-500 text-sm font-semibold py-1 px-3 rounded-md transition duration-300 shadow">
    <span class="hidden lg:inline">Add your product &rarr;</span>
    <span class="lg:hidden">Submit &rarr;</span>
</a> -->

    <style>
        .loader {
            position: relative;
            width: 60px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loader .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ff5c5c;
            margin: 0 3px;
            animation: dotPulse 1.4s infinite ease-in-out;
        }

        .loader .dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .loader .dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        .loader .dot:nth-child(3) {
            animation-delay: 0s;
        }

        @keyframes dotPulse {
            0%, 60%, 100% {
                transform: scale(0.6);
                opacity: 0.4;
            }
            30% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>



    <!-- Button -->
    <a href="#"
       id="submit-button"
       class="bg-white hover:bg-gray-50 text-gray-700 text-base font-semibold border-2 border-gray-200 py-1 px-3 rounded-md transition duration-300 inline-flex items-center justify-center gap-2 relative">

        <!-- Text that gets replaced -->
        <span id="button-content" class="flex items-center gap-2">
            <span class="hidden lg:inline">
            <div class="flex flex-row items-center gap-1.5">   
                 <div>
                <svg class="w-5 h-5 fill-gray-700" viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>plus_circle [#1427]</title> <desc>Cricled Plus Icon.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-179.000000, -600.000000)" fill="#4d4d4d"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M137.7,450 C137.7,450.552 137.2296,451 136.65,451 L134.55,451 L134.55,453 C134.55,453.552 134.0796,454 133.5,454 C132.9204,454 132.45,453.552 132.45,453 L132.45,451 L130.35,451 C129.7704,451 129.3,450.552 129.3,450 C129.3,449.448 129.7704,449 130.35,449 L132.45,449 L132.45,447 C132.45,446.448 132.9204,446 133.5,446 C134.0796,446 134.55,446.448 134.55,447 L134.55,449 L136.65,449 C137.2296,449 137.7,449.448 137.7,450 M133.5,458 C128.86845,458 125.1,454.411 125.1,450 C125.1,445.589 128.86845,442 133.5,442 C138.13155,442 141.9,445.589 141.9,450 C141.9,454.411 138.13155,458 133.5,458 M133.5,440 C127.70085,440 123,444.477 123,450 C123,455.523 127.70085,460 133.5,460 C139.29915,460 144,455.523 144,450 C144,444.477 139.29915,440 133.5,440" id="plus_circle-[#1427]"> </path> </g> </g> </g> </g></svg>
                </div>

                <div>
            Submit product 
                </div>
               
               </div> 
            </span>
         
            <span class="lg:hidden">Submit &rarr;</span>
        </span>
    </a>

    <script>
        const button = document.getElementById('submit-button');
        const content = document.getElementById('button-content');

        button.addEventListener('click', function (e) {
            e.preventDefault(); // For demo

            // Get current size
            const width = button.offsetWidth;
            const height = button.offsetHeight;

            // Lock size to prevent collapsing
            button.style.width = width + 'px';
            button.style.minHeight = height + 'px';

            // Replace content with loader
            content.innerHTML = `
                <div class="loader">
                    <div class="dot"></div>
                    <div class="dot"></div>
                    <div class="dot"></div>
                </div>
            `;

            // Optional: Redirect (commented out for demo)
             setTimeout(() => {
                window.location.href = "{{ route('products.create') }}";
              }, 0);
        });
    </script>


