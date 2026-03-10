import { expect, test, type Locator, type Page } from '@playwright/test';

declare const process: { env: Record<string, string | undefined> };

const env = process.env;

const CUSTOMER_EMAIL = env.E2E_CUSTOMER_EMAIL || 'e2e.customer@ftos.test';
const CUSTOMER_PASSWORD = env.E2E_CUSTOMER_PASSWORD || 'E2ePassword123!';
const TRUCK_NAME = 'E2E UX Truck';
const BURGER_MENU = 'E2E Chicken Burger';
const SECOND_TRUCK_NAME = 'E2E Sunset Truck';
const SECOND_TRUCK_MENU = 'E2E Wrap';

async function loginAsCustomer(page: Page) {
  await page.goto('/login');
  await page.fill('#email', CUSTOMER_EMAIL);
  await page.fill('#password', CUSTOMER_PASSWORD);
  await page.getByRole('button', { name: /^Log in$/i }).click();
  await page.waitForURL('**/customer/dashboard');
}

async function openFixtureTruckMenu(page: Page) {
  await openTruckMenu(page, TRUCK_NAME);
}

async function openTruckMenu(page: Page, truckName: string) {
  await page.goto('/customer/browse');
  await expect(page.getByRole('heading', { name: /Browse Food Trucks/i })).toBeVisible();

  const truckLink = page.locator('a', { hasText: truckName }).first();
  await expect(truckLink).toBeVisible();
  await truckLink.click();

  await page.waitForURL('**/customer/truck/*');
  await expect(page.getByRole('heading', { name: truckName })).toBeVisible();
}

async function openMenuCustomizeModal(page: Page, menuName: string) {
  const menuCard = page.locator('div.cursor-pointer', { hasText: menuName }).first();
  await expect(menuCard).toBeVisible();
  await menuCard.click();

  await expect(page.getByRole('button', { name: /Add to Cart|Update Cart/i })).toBeVisible();
}

function customizeModal(page: Page): Locator {
  return page.locator('div.fixed.inset-0').filter({
    has: page.getByRole('button', { name: /Add to Cart|Update Cart/i }),
  }).first();
}

function modalTotal(modal: Locator): Locator {
  return modal.locator('span.text-xl.font-black.text-gray-900', { hasText: /^RM\s/ });
}

function cartSidebar(page: Page): Locator {
  return page.locator('div.w-80').filter({ hasText: 'Your Cart' }).first();
}

test.describe('Customer browser-level pricing and cart flow', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsCustomer(page);
  });

  test('single-selection group blocks add-to-cart until one option is selected', async ({ page }) => {
    await openFixtureTruckMenu(page);
    await openMenuCustomizeModal(page, BURGER_MENU);

    const addToCartButton = page.getByRole('button', { name: /Add to Cart/i });
    await expect(addToCartButton).toBeDisabled();
    await expect(page.getByText('Please make a selection for all required options.')).toBeVisible();

    await page.getByRole('button', { name: /Regular Chicken/i }).first().click();
    await expect(addToCartButton).toBeEnabled();
  });

  test('modal and cart totals update correctly for mixed zero and non-zero choices', async ({ page }) => {
    await openFixtureTruckMenu(page);
    await openMenuCustomizeModal(page, BURGER_MENU);

    const modal = customizeModal(page);

    await page.getByRole('button', { name: /Regular Chicken/i }).first().click();
    await page.getByRole('button', { name: /Cheese/i }).first().click();
    await page.getByRole('button', { name: /Egg/i }).first().click();

    const quantitySection = modal.locator('p', { hasText: 'Quantity' }).first().locator('xpath=..');
    await quantitySection.locator('button').nth(1).click();
    await quantitySection.locator('button').nth(1).click();

    await expect(modalTotal(modal)).toHaveText('RM 3.00');

    await modal.getByRole('button', { name: /Add to Cart/i }).click();

    const sidebar = cartSidebar(page);
    await expect(sidebar.locator('span.text-lg.font-black.text-gray-900').first()).toHaveText('RM 3.00');
  });

  test('editing cart item recalculates customer-visible total', async ({ page }) => {
    await openFixtureTruckMenu(page);
    await openMenuCustomizeModal(page, BURGER_MENU);

    const modal = customizeModal(page);

    await page.getByRole('button', { name: /Spicy Chicken/i }).first().click();
    await page.getByRole('button', { name: /Egg/i }).first().click();

    const quantitySection = modal.locator('p', { hasText: 'Quantity' }).first().locator('xpath=..');
    await quantitySection.locator('button').nth(1).click();

    await expect(modalTotal(modal)).toHaveText('RM 4.00');
    await modal.getByRole('button', { name: /Add to Cart/i }).click();

    const sidebar = cartSidebar(page);
    await expect(sidebar.locator('span.text-lg.font-black.text-gray-900').first()).toHaveText('RM 4.00');

    await sidebar.getByRole('button', { name: /Edit/i }).first().click();

    const editModal = customizeModal(page);
    await page.getByRole('button', { name: /Egg/i }).first().click();

    const editQuantitySection = editModal.locator('p', { hasText: 'Quantity' }).first().locator('xpath=..');
    await editQuantitySection.locator('button').first().click();

    await expect(modalTotal(editModal)).toHaveText('RM 1.00');
    await editModal.getByRole('button', { name: /Update Cart/i }).click();

    await expect(sidebar.locator('span.text-lg.font-black.text-gray-900').first()).toHaveText('RM 1.00');
  });

  test('checkout receipt total matches cart total for customer', async ({ page }) => {
    await openFixtureTruckMenu(page);
    await openMenuCustomizeModal(page, BURGER_MENU);

    const modal = customizeModal(page);

    await page.getByRole('button', { name: /Regular Chicken/i }).first().click();
    await page.getByRole('button', { name: /Egg/i }).first().click();

    const quantitySection = modal.locator('p', { hasText: 'Quantity' }).first().locator('xpath=..');
    await quantitySection.locator('button').nth(1).click();

    await expect(modalTotal(modal)).toHaveText('RM 2.00');
    await modal.getByRole('button', { name: /Add to Cart/i }).click();

    const sidebar = cartSidebar(page);
    await expect(sidebar.locator('span.text-lg.font-black.text-gray-900').first()).toHaveText('RM 2.00');

    const checkoutButton = sidebar.getByRole('button', { name: /Checkout/i }).first();
    await expect(checkoutButton).toBeVisible();
    await expect(checkoutButton).toBeEnabled();
    await checkoutButton.click();

    const paymentModal = page.locator('div.fixed.inset-0').filter({
      has: page.getByText('Choose Payment Method'),
    }).first();

    await expect(paymentModal.getByText('Choose Payment Method')).toBeVisible();
    await paymentModal.getByRole('button', { name: /Cash/i }).first().click();
    await expect(page.getByRole('heading', { name: 'Cash Payment' })).toBeVisible();
    await page.getByRole('button', { name: /Confirm Order/i }).click();

    await expect(page.getByText('Order Placed!')).toBeVisible();
    await page.getByRole('button', { name: /Show Receipt/i }).click();

    const receiptModal = page.locator('div.fixed.inset-0').filter({
      has: page.getByRole('heading', { name: /Order Receipt/i }),
    }).first();

    await expect(receiptModal.getByRole('heading', { name: /Order Receipt/i })).toBeVisible();
    await expect(receiptModal.locator('span.text-lg.font-black.text-gray-900').first()).toHaveText('RM 2.00');
    await expect(receiptModal.getByText('Cash', { exact: true })).toBeVisible();
  });

  test('cart groups items by truck and enforces single-truck checkout selection', async ({ page }) => {
    await openTruckMenu(page, TRUCK_NAME);
    await openMenuCustomizeModal(page, BURGER_MENU);

    await page.getByRole('button', { name: /Regular Chicken/i }).first().click();
    await customizeModal(page).getByRole('button', { name: /Add to Cart/i }).click();

    await openTruckMenu(page, SECOND_TRUCK_NAME);
    await openMenuCustomizeModal(page, SECOND_TRUCK_MENU);
    await customizeModal(page).getByRole('button', { name: /Add to Cart/i }).click();

    const sidebar = cartSidebar(page);
    await expect(sidebar.getByText(TRUCK_NAME, { exact: true })).toBeVisible();
    await expect(sidebar.getByText(SECOND_TRUCK_NAME, { exact: true })).toBeVisible();

    await sidebar.getByRole('button', { name: /Checkout/i }).first().click();
    await expect(sidebar.getByText('Please select items from one food truck only before checkout.')).toBeVisible();
  });
});
