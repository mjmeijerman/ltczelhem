document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.delete-button');
    const deleteModal = document.getElementById('deleteModal');
    const cancelButton = document.getElementById('cancelButton');
    const deleteForm = document.getElementById('deleteForm');
    const userEmailSpan = document.getElementById('userEmail');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.preventDefault();
            const deleteUrl = this.getAttribute('href');
            const userEmail = this.getAttribute('data-email');
            deleteForm.setAttribute('action', deleteUrl);
            userEmailSpan.textContent = userEmail;
            deleteModal.classList.remove('hidden');
        });
    });

    cancelButton.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
    });

    const addUserButton = document.querySelector('.add-user-button');
    const addUserModal = document.getElementById('addUserModal');
    const cancelAddUserButton = document.getElementById('cancelAddUserButton');
    const addUserForm = document.getElementById('addUserForm');

    addUserButton.addEventListener('click', function(event) {
        event.preventDefault();
        addUserModal.classList.remove('hidden');
    });

    cancelAddUserButton.addEventListener('click', function() {
        addUserModal.classList.add('hidden');
    });

    addUserForm.addEventListener('submit', function(event) {
        const rolesSelect = document.getElementById('roles');
        const selectedRoles = Array.from(rolesSelect.selectedOptions).map(option => option.value);
        const rolesInput = document.createElement('input');
        rolesInput.type = 'hidden';
        rolesInput.name = 'roles';
        rolesInput.value = JSON.stringify(selectedRoles);
        addUserForm.appendChild(rolesInput);
    });
});