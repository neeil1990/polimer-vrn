function customCountUp(element, startValue, endValue, duration = 1000) {
    const range = endValue - startValue
    const increment = range / (duration / 16) // Количество приращений каждые 16 миллисекунд (60 FPS)

    let currentValue = startValue
    let startTime = null

    function updateCount(timestamp) {
        if (!startTime) startTime = timestamp
        const elapsedTime = timestamp - startTime

        currentValue = startValue + range * (elapsedTime / duration)

        if (elapsedTime < duration) {
            requestAnimationFrame(updateCount)
        } else {
            currentValue = endValue // Установка окончательного значения
        }

        const formattedValue = currentValue.toLocaleString('ru-RU', { maximumFractionDigits: 0 })
        element.textContent = formattedValue // Округление и обновление текста элемента
    }

    const formattedEndValue = endValue.toLocaleString('ru-RU', { maximumFractionDigits: 0 })
    element.textContent = formattedEndValue // Установка исходного значения с разделителем тысячных

    requestAnimationFrame(updateCount)
}