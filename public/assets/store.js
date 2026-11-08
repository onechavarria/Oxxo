const method = document.querySelector('#method');
if (method) {
    const address = document.querySelector('#address-field');
    const shipping = document.querySelector('#shipping');
    const total = document.querySelector('#total');
    const format = new Intl.NumberFormat('es-CL', {style: 'currency', currency: 'CLP'});
    const update = () => {
        const delivery = method.value === 'delivery';
        const fee = delivery ? 1990 : 0;
        address.hidden = !delivery;
        address.querySelector('input').required = delivery;
        shipping.textContent = format.format(fee);
        total.textContent = format.format(Number(total.dataset.base) + fee);
    };
    method.addEventListener('change', update);
    update();
}
