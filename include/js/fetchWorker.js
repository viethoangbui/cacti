self.onmessage = async (event) => {
    try {
        const url = event.data.url;

        const response = await fetch(url, { method: 'GET' });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();
        data.key = event.data.key;

        // Send the data back to the main thread
        self.postMessage(data);
    } catch (error) {
        self.postMessage({ error: error.message });
    }
};
