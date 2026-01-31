<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dialog</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="p-10 bg-background" x-data>
    <h1 class="text-2xl font-bold mb-5 text-text">Test Alpine.js Confirm Dialog</h1>

    <!-- Test 1: Simple Alpine -->
    <div x-data="{ count: 0 }" class="mb-5 p-4 bg-surface rounded-lg shadow border border-border">
        <h2 class="font-bold text-text">Test 1: Basic Alpine</h2>
        <p class="text-text">Count: <span x-text="count"></span></p>
        <button @click="count++" class="px-4 py-2 bg-primary text-white rounded-lg mt-2">Increment</button>
    </div>

    <!-- Test 2: $dispatch test -->
    <div x-data class="mb-5 p-4 bg-surface rounded-lg shadow border border-border">
        <h2 class="font-bold text-text">Test 2: $dispatch to window</h2>
        <button @click="console.log('clicked'); $dispatch('confirm', { title: 'Test', message: 'Hello!' })"
                class="px-4 py-2 bg-success text-white rounded-lg mt-2">
            Dispatch Confirm Event
        </button>
    </div>

    <!-- Test 3: Check if confirmDialog exists -->
    <div x-data class="mb-5 p-4 bg-surface rounded-lg shadow border border-border">
        <h2 class="font-bold text-text">Test 3: Check confirmDialog function</h2>
        <button onclick="console.log('confirmDialog exists:', typeof window.confirmDialog); alert('confirmDialog exists: ' + (typeof window.confirmDialog))"
                class="px-4 py-2 bg-warning text-white rounded-lg mt-2">
            Check confirmDialog
        </button>
    </div>

    <!-- Test 4: Manual window event -->
    <div class="mb-5 p-4 bg-surface rounded-lg shadow border border-border">
        <h2 class="font-bold text-text">Test 4: Manual Window Event</h2>
        <button onclick="console.log('dispatching...'); window.dispatchEvent(new CustomEvent('confirm', { detail: { title: 'Manual Test', message: 'This is manual dispatch' } }))"
                class="px-4 py-2 bg-danger text-white rounded-lg mt-2">
            Manual Dispatch
        </button>
    </div>

    <!-- The Confirm Dialog Component -->
    <x-delete-confirm />

    <div class="mt-10 p-4 bg-warning-100 rounded-lg border border-warning-200">
        <h2 class="font-bold text-text">Debug Info:</h2>
        <div x-data x-init="console.log('Alpine initialized'); console.log('confirmDialog type:', typeof window.confirmDialog)">
            <p class="text-muted">Check browser console for logs</p>
        </div>
    </div>
</body>
</html>
