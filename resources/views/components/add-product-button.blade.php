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
            background-color: #ec4899;
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
       class="bg-white hover:bg-rose-50 text-primary-500 border border-primary-500 text-sm font-semibold py-1 px-3 rounded-md transition duration-300 shadow inline-flex items-center justify-center gap-2 relative">

        <!-- Text that gets replaced -->
        <span id="button-content" class="flex items-center gap-2">
            <span class="hidden lg:inline">Add your product &rarr;</span>
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


