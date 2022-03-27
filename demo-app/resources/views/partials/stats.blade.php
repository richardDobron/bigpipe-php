<div class="relative z-10 rounded-md bg-white p-4 text-left text-sm shadow-lg ring-1 ring-black ring-opacity-5">
    <div class="sm:grid sm:h-32 sm:grid-flow-row sm:gap-4 sm:grid-cols-2">
        <div class="flex flex-col justify-center px-4 py-4 bg-white border-4 border-dashed border-slate-200 rounded">
            <div>
                <div>
                    <p class="flex items-center justify-end text-green-500 text-md">
                        <span class="font-bold">{{ rand(1, 100) }}%</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path class="heroicon-ui" d="M20 15a1 1 0 002 0V7a1 1 0 00-1-1h-8a1 1 0 000 2h5.59L13 13.59l-3.3-3.3a1 1 0 00-1.4 0l-6 6a1 1 0 001.4 1.42L9 12.4l3.3 3.3a1 1 0 001.4 0L20 9.4V15z"></path></svg>
                    </p>
                </div>
                <p class="text-3xl font-semibold text-center text-gray-800">{{ rand(50, 250) }}</p>
                <p class="text-lg text-center text-gray-500">Orders</p>
            </div>
        </div>

        <div class="flex flex-col justify-center px-4 py-4 mt-4 bg-white border-4 border-dashed border-slate-200 rounded sm:mt-0">
            <div>
                <div>
                    <p class="flex items-center justify-end text-red-500 text-md">
                        <span class="font-bold">{{ rand(1, 100) }}%</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 fill-current" viewBox="0 0 24 24"><path class="heroicon-ui" d="M20 9a1 1 0 012 0v8a1 1 0 01-1 1h-8a1 1 0 010-2h5.59L13 10.41l-3.3 3.3a1 1 0 01-1.4 0l-6-6a1 1 0 011.4-1.42L9 11.6l3.3-3.3a1 1 0 011.4 0l6.3 6.3V9z"></path></svg>
                    </p>
                </div>
                <p class="text-3xl font-semibold text-center text-gray-800">{{ rand(50, 250) }}</p>
                <p class="text-lg text-center text-gray-500">Users</p>
            </div>
        </div>
    </div>
</div>
