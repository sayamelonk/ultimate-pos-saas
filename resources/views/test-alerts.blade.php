<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Alerts & Dialogs</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background min-h-screen" x-data>
    <div class="container mx-auto p-8 max-w-4xl">

        <div class="mb-8">
            <h1 class="text-3xl font-bold text-text">Test Alerts & Dialogs</h1>
            <p class="text-muted mt-2">Halaman ini untuk testing semua komponen alert dan dialog</p>
        </div>

        <!-- ==================== ALERT COMPONENTS ==================== -->

        <div class="mb-10">
            <h2 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
                <span class="w-2 h-8 bg-primary rounded"></span>
                Alert Components
            </h2>

            <div class="space-y-4">
                <!-- Info Alert -->
                <div class="p-4 bg-surface rounded-lg border border-border">
                    <h3 class="font-medium text-text mb-2">Info Alert</h3>
                    <x-alert type="info" :dismissible="true">
                        This is an info alert message. Click the X to dismiss.
                    </x-alert>
                </div>

                <!-- Success Alert -->
                <div class="p-4 bg-surface rounded-lg border border-border">
                    <h3 class="font-medium text-text mb-2">Success Alert</h3>
                    <x-alert type="success" title="Success!" :dismissible="true">
                        Operation completed successfully. Your data has been saved.
                    </x-alert>
                </div>

                <!-- Warning Alert -->
                <div class="p-4 bg-surface rounded-lg border border-border">
                    <h3 class="font-medium text-text mb-2">Warning Alert</h3>
                    <x-alert type="warning" title="Warning!" :dismissible="true">
                        Your stock is running low. Please consider reordering soon.
                    </x-alert>
                </div>

                <!-- Danger Alert -->
                <div class="p-4 bg-surface rounded-lg border border-border">
                    <h3 class="font-medium text-text mb-2">Danger Alert</h3>
                    <x-alert type="danger" title="Error!" :dismissible="true">
                        Failed to process your request. Please try again or contact support.
                    </x-alert>
                </div>

                <!-- Non-dismissible Alert -->
                <div class="p-4 bg-surface rounded-lg border border-border">
                    <h3 class="font-medium text-text mb-2">Non-dismissible Alert</h3>
                    <x-alert type="info">
                        This alert cannot be dismissed. You must acknowledge it by navigating away.
                    </x-alert>
                </div>
            </div>
        </div>

        <!-- ==================== TOAST NOTIFICATIONS ==================== -->

        <div class="mb-10">
            <h2 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
                <span class="w-2 h-8 bg-success rounded"></span>
                Toast Notifications (Click to Test)
            </h2>

            <div x-data="toastTester()" class="p-4 bg-surface rounded-lg border border-border">
                <div class="flex flex-wrap gap-3">
                    <button @click="showSuccess()" class="px-4 py-2 bg-success text-white rounded-lg hover:bg-success-600 transition">
                        Show Success Toast
                    </button>
                    <button @click="showError()" class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger-600 transition">
                        Show Error Toast
                    </button>
                    <button @click="showWarning()" class="px-4 py-2 bg-warning text-white rounded-lg hover:bg-warning-600 transition">
                        Show Warning Toast
                    </button>
                    <button @click="showInfo()" class="px-4 py-2 bg-info text-white rounded-lg hover:bg-info-600 transition">
                        Show Info Toast
                    </button>
                </div>

                <div class="mt-4 p-3 bg-background rounded border border-border">
                    <p class="text-sm text-muted">Status: <span x-text="lastToast" class="font-medium text-text">None</span></p>
                </div>
            </div>
        </div>

        <!-- ==================== CONFIRM DIALOGS ==================== -->

        <div class="mb-10">
            <h2 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
                <span class="w-2 h-8 bg-warning rounded"></span>
                Confirm Dialogs
            </h2>

            <div class="p-4 bg-surface rounded-lg border border-border space-y-4">
                <div class="flex flex-wrap gap-3">
                    <button @click="$dispatch('confirm', {
                        title: 'Delete Item',
                        message: 'Are you sure you want to delete this item? This action cannot be undone.',
                        confirmText: 'Delete',
                        cancelText: 'Cancel'
                    })" class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger-600 transition">
                        Delete Confirmation
                    </button>

                    <button @click="$dispatch('confirm', {
                        title: 'Save Changes',
                        message: 'Do you want to save your changes before leaving?',
                        confirmText: 'Save',
                        cancelText: 'Discard'
                    })" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition">
                        Save Confirmation
                    </button>

                    <button @click="$dispatch('confirm', {
                        title: 'Process Order',
                        message: 'Process this order now? This will charge the customer.',
                        confirmText: 'Process',
                        cancelText: 'Cancel'
                    })" class="px-4 py-2 bg-info text-white rounded-lg hover:bg-info-600 transition">
                        Process Confirmation
                    </button>
                </div>

                <div x-data="{ confirmed: false }" class="mt-4 p-3 bg-background rounded border border-border">
                    <p class="text-sm text-muted">Last Action: <span x-text="confirmed ? 'Confirmed!' : 'None yet'" class="font-medium text-text"></span></p>
                    <div x-show="confirmed" x-transition class="mt-2 text-success text-sm">✓ Dialog was confirmed</div>
                </div>
            </div>
        </div>

        <!-- ==================== MODAL DIALOGS ==================== -->

        <div class="mb-10">
            <h2 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
                <span class="w-2 h-8 bg-info rounded"></span>
                Modal Dialogs
            </h2>

            <div class="p-4 bg-surface rounded-lg border border-border space-y-4">
                <div x-data="{ openModal: false }">
                    <button @click="openModal = true" class="px-4 py-2 bg-info text-white rounded-lg hover:bg-info-600 transition">
                        Open Simple Modal
                    </button>

                    <!-- Simple Modal -->
                    <div x-show="openModal"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
                         @click.away="openModal = false"
                         style="display: none;">
                        <div x-show="openModal"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-200"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="bg-surface rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
                            <h3 class="text-lg font-bold text-text mb-2">Simple Modal</h3>
                            <p class="text-muted mb-4">This is a simple modal dialog using Alpine.js.</p>
                            <div class="flex justify-end gap-2">
                                <button @click="openModal = false" class="px-4 py-2 bg-secondary text-text rounded-lg hover:bg-secondary-200 transition">
                                    Close
                                </button>
                                <button @click="openModal = false" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition">
                                    Confirm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Modal -->
                <div x-data="{ openForm: false }">
                    <button @click="openForm = true" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition">
                        Open Form Modal
                    </button>

                    <div x-show="openForm"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
                         @click.away="openForm = false"
                         style="display: none;">
                        <div class="bg-surface rounded-lg shadow-xl p-6 max-w-md w-full">
                            <h3 class="text-lg font-bold text-text mb-4">Add New Item</h3>
                            <form @submit.prevent="openForm = false">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-text mb-1">Name</label>
                                        <input type="text" class="w-full px-3 py-2 border border-border rounded-lg bg-background text-text focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Enter name">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-text mb-1">Email</label>
                                        <input type="email" class="w-full px-3 py-2 border border-border rounded-lg bg-background text-text focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Enter email">
                                    </div>
                                </div>
                                <div class="flex justify-end gap-2 mt-6">
                                    <button type="button" @click="openForm = false" class="px-4 py-2 bg-secondary text-text rounded-lg hover:bg-secondary-200 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition">
                                        Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== LOADING STATES ==================== -->

        <div class="mb-10">
            <h2 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
                <span class="w-2 h-8 bg-muted rounded"></span>
                Loading States
            </h2>

            <div class="p-4 bg-surface rounded-lg border border-border">
                <div x-data="{ loading: false }" class="space-y-4">
                    <div>
                        <button @click="loading = true; setTimeout(() => loading = false, 2000)" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition flex items-center gap-2">
                            <span x-show="!loading">Click to Load (2s)</span>
                            <span x-show="loading" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading...
                            </span>
                        </button>
                    </div>

                    <!-- Loading Spinner -->
                    <div class="flex items-center gap-4 p-3 bg-background rounded border border-border">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                        <span class="text-muted">Loading spinner example</span>
                    </div>

                    <!-- Progress Bar -->
                    <div x-data="{ progress: 0 }" x-init="setInterval(() => progress = progress >= 100 ? 0 : progress + 10, 500)" class="p-3 bg-background rounded border border-border">
                        <div class="flex justify-between text-sm text-muted mb-2">
                            <span>Progress</span>
                            <span x-text="progress + '%'"></span>
                        </div>
                        <div class="w-full bg-secondary rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== ALERT VARIATIONS ==================== -->

        <div class="mb-10">
            <h2 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
                <span class="w-2 h-8 bg-danger rounded"></span>
                Alert Scenarios
            </h2>

            <div class="space-y-4">
                <!-- Low Stock Warning -->
                <x-alert type="warning" title="Low Stock Alert" :dismissible="true">
                    <strong>5 items</strong> are below reorder level. <a href="#" class="underline">View items</a>
                </x-alert>

                <!-- Expired Items -->
                <x-alert type="danger" title="Expired Items" :dismissible="true">
                    <strong>3 batches</strong> have expired and will be blocked from sales.
                </x-alert>

                <!-- System Notice -->
                <x-alert type="info" title="System Maintenance" :dismissible="true">
                    Scheduled maintenance on <strong>Sunday 2:00 AM - 4:00 AM</strong>. System will be unavailable.
                </x-alert>

                <!-- Success Message -->
                <x-alert type="success" :dismissible="true">
                    ✓ Purchase Order <strong>PO-2024-001</strong> has been created successfully. <a href="#" class="underline">View details</a>
                </x-alert>
            </div>
        </div>

        <!-- ==================== Delete Confirm Component ==================== -->

        <div class="mb-10">
            <h2 class="text-xl font-bold text-text mb-4 flex items-center gap-2">
                <span class="w-2 h-8 bg-danger rounded"></span>
                Delete Confirm Component
            </h2>

            <div class="p-4 bg-surface rounded-lg border border-border">
                <button @click="$dispatch('confirm', {
                    title: 'Delete This Item?',
                    message: 'This will permanently delete the item. This action cannot be undone.',
                    confirmText: 'Delete',
                    cancelText: 'Cancel'
                })" class="px-4 py-2 bg-danger text-white rounded-lg hover:bg-danger-600 transition">
                    Test Delete Confirm
                </button>
                <p class="text-sm text-muted mt-2">Click to test the <code>&lt;x-delete-confirm /&gt;</code> component</p>
            </div>
        </div>

    </div>

    <!-- Include the delete confirm component -->
    <x-delete-confirm />

    <script>
        function toastTester() {
            return {
                lastToast: 'None',
                showSuccess() {
                    this.lastToast = 'Success toast shown!';
                    // Add your toast notification logic here
                    alert('Success: Operation completed!');
                },
                showError() {
                    this.lastToast = 'Error toast shown!';
                    alert('Error: Something went wrong!');
                },
                showWarning() {
                    this.lastToast = 'Warning toast shown!';
                    alert('Warning: Please check your input!');
                },
                showInfo() {
                    this.lastToast = 'Info toast shown!';
                    alert('Info: Here is some information.');
                }
            }
        }
    </script>
</body>
</html>
