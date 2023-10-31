function customCountUp(element, startValue, endValue, duration = 1000) {
    const range = endValue - startValue
    const increment = range / (duration / 16) // ���������� ���������� ������ 16 ����������� (60 FPS)

    let currentValue = startValue
    let startTime = null

    function updateCount(timestamp) {
        if (!startTime) startTime = timestamp
        const elapsedTime = timestamp - startTime

        currentValue = startValue + range * (elapsedTime / duration)

        if (elapsedTime < duration) {
            requestAnimationFrame(updateCount)
        } else {
            currentValue = endValue // ��������� �������������� ��������
        }

        const formattedValue = currentValue.toLocaleString('ru-RU', { maximumFractionDigits: 0 })
        element.textContent = formattedValue // ���������� � ���������� ������ ��������
    }

    const formattedEndValue = endValue.toLocaleString('ru-RU', { maximumFractionDigits: 0 })
    element.textContent = formattedEndValue // ��������� ��������� �������� � ������������ ��������

    requestAnimationFrame(updateCount)
}