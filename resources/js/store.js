Statamic.$store.registerModule(['publish', 'advancedSeo'], {

    namespaced: true,

    state: {
        conditions: {},
    },

    getters: {
        conditions: state => state.conditions,
    },

    actions: {
        async getConditions({commit}, payload) {
            const conditions = await fetch(`https://statamic-advanced-seo.test/!/advanced-seo/conditions/${payload.id}`);
            commit('SET_CONDITIONS', await conditions.json());
        },
    },

    mutations: {
        SET_CONDITIONS(state, conditions) {
            state.conditions = conditions;
        },
    }

})
