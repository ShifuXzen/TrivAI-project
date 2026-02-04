/**
 * API Integration Tests
 * Run with: node api_integration_tests.js
 * 
 * Deze tests verifiëren of de API endpoints correct reageren volgens de specificatie.
 * Opmerking: Dit vereist een draaiende backend op http://localhost:3000 (of pas BASE_URL aan).
 */

const BASE_URL = 'http://localhost:3000/api/v1';

// Simpele test helper
async function test(name, fn) {
    try {
        process.stdout.write(`TEST: ${name} ... `);
        await fn();
        console.log('✅ PASS');
    } catch (e) {
        console.log('❌ FAIL');
        console.error('   ', e.message);
    }
}

async function request(endpoint, options = {}) {
    const url = `${BASE_URL}${endpoint}`;
    const headers = { 'Content-Type': 'application/json', ...options.headers };

    // Voeg mock token toe indien nodig
    // headers['Authorization'] = 'Bearer mock-token';

    const res = await fetch(url, { ...options, headers });
    const data = await res.json().catch(() => ({})); // Flexibele JSON parsing

    return { status: res.status, data };
}

(async () => {
    console.log(`🚀 Starting API Integration Tests against ${BASE_URL}\n`);

    // --- 1. CATALOGUS TESTS ---

    await test('GET /catalogus/boeken - Moet lijst van boeken teruggeven', async () => {
        const { status, data } = await request('/catalogus/boeken');
        if (status !== 200) throw new Error(`Status ${status} is niet 200`);
        if (!Array.isArray(data)) throw new Error('Response is geen array');
    });

    await test('GET /catalogus/boeken/:id - Moet details geven van bestaand boek', async () => {
        // We gaan er even vanuit dat ID '123' bestaat
        const { status, data } = await request('/catalogus/boeken/123');
        if (status !== 200) throw new Error(`Status ${status} is niet 200`);
        if (!data.titel) throw new Error('Boek data mist titel');
        if (!data.locatie) throw new Error('Boek data mist locatie');
    });

    // --- 2. TRANSACTIES TESTS ---

    await test('POST /transacties/lenen - Moet boek succesvol uitlenen', async () => {
        const payload = { lidId: 'LID-001', boekId: '123' };
        const { status, data } = await request('/transacties/lenen', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        // Verwacht 200 OK of 201 Created
        if (status !== 200 && status !== 201) throw new Error(`Status ${status} onverwacht bij lenen`);
    });

    await test('POST /transacties/retourneren - Moet boek aannemen', async () => {
        const payload = { exemplaarId: 'EX-999' };
        const { status } = await request('/transacties/retourneren', {
            method: 'POST',
            body: JSON.stringify(payload)
        });

        if (status !== 200) throw new Error(`Status ${status} is niet 200`);
    });

    // --- 3. ACCOUNT TESTS ---

    await test('GET /account/profiel - Moet gebruikersinfo tonen', async () => {
        const { status, data } = await request('/account/profiel');
        if (status !== 200) throw new Error(`Status ${status} is niet 200`);
        if (!data.email) throw new Error('Profiel mist email veld');
    });

    console.log('\n🏁 Tests afgerond.');
})();
