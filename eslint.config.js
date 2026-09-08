import js from '@eslint/js';
import pluginVue from 'eslint-plugin-vue';
import tseslint from 'typescript-eslint';
import globals from 'globals';

export default tseslint.config(
    {
        ignores: [
            'vendor',
            'public/build',
            'bootstrap/ssr',
            'node_modules',
            'public/hot',
            '**/*.d.ts',
            'resources/js/ziggy.js',
        ],
    },
    js.configs.recommended,
    ...tseslint.configs.recommended,
    ...pluginVue.configs['flat/recommended'],
    {
        files: ['resources/js/**/*.{ts,vue}'],
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                route: 'readonly',
                axios: 'readonly',
            },
            parserOptions: {
                parser: tseslint.parser,
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/html-self-closing': 'off',
            'vue/singleline-html-element-content-newline': 'off',
            'vue/first-attribute-linebreak': 'off',
            'no-console': ['warn', { allow: ['warn', 'error'] }],
        },
    },
);