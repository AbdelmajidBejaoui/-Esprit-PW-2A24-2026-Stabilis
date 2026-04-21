# TODO - Move inline styles from front PHP pages to assets CSS

- [x] Create `assets/css/front-pages.css` with styles extracted from `Views/front/cart.php` and `Views/front/index.php`
- [x] Update `Views/front/cart.php`:
  - [x] Add stylesheet link to `front-pages.css`
  - [x] Remove inline `<style>` block
  - [x] Replace inline `style=""` attributes with CSS classes
- [x] Update `Views/front/index.php`:
  - [x] Add stylesheet link to `front-pages.css`
  - [x] Remove inline `<style>` block
  - [x] Replace inline `style=""` attributes with CSS classes
- [ ] Run a quick consistency check to ensure no leftover inline styles in these two files
