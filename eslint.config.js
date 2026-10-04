import prettier from 'eslint-config-prettier';
import vue from 'eslint-plugin-vue';
import typescriptEslint from 'typescript-eslint';

export default [
    ...vue.configs['flat/essential'],
    ...typescriptEslint.configs.recommended,
    {
        files: ['resources/js/**/*.{ts,vue}'],
        languageOptions: {
            parserOptions: {
                parser: typescriptEslint.parser,
                extraFileExtensions: ['.vue'],
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            '@typescript-eslint/no-explicit-any': 'off',
        },
    },
    {
        ignores: ['vendor', 'node_modules', 'public', 'bootstrap/ssr', 'tailwind.config.js', 'resources/js/components/ui/*'],
    },
    prettier,
];
