const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? '/tmp/contapro-browser/node_modules/playwright/index.mjs');
import { mkdir, writeFile } from 'node:fs/promises';
const phase = process.argv[2] ?? 'before';
if (!['before','after'].includes(phase)) throw new Error('Use before or after');
const output = new URL(`./${phase}`, import.meta.url).pathname;
await mkdir(output, {recursive:true});
const baseURL=process.env.APP_URL ?? 'http://localhost:8000';
const browser = await chromium.launch({args:['--no-sandbox',...(process.env.APP_HOST_IP ? [`--host-resolver-rules=MAP localhost ${process.env.APP_HOST_IP}`] : [])]});
const context = await browser.newContext({viewport:{width:1440,height:900}, colorScheme:'light'});
const page = await context.newPage();
const errors = [];
page.on('pageerror', e => errors.push(String(e)));
page.on('response', r => { if(r.status() >= 400) errors.push(`${r.status()} ${r.url()}`); });
await page.goto(`${baseURL}/login`, {waitUntil:'networkidle'});
await page.screenshot({path:`${output}/login-1440.png`,fullPage:true});
await page.setViewportSize({width:390,height:844});
await page.screenshot({path:`${output}/login-390.png`,fullPage:true});
await page.getByRole('button',{name:'Preencher conta demo'}).click();
await page.getByRole('button',{name:'Entrar',exact:true}).click();
await page.waitForURL('**/dashboard');
const routes = ['dashboard','transactions','accounts','portfolios','reports','agenda','expected-incomes','recurring','installments','budgets','goals','assets','brokers','banks','categories','imports','reconciliations','profile'];
const results = [];
for(const route of routes){
 await page.goto(`${baseURL}/${route}`,{waitUntil:'networkidle'});
 for(const width of [1440,390]) {
  await page.setViewportSize({width,height:width === 390 ? 844:900});
  await page.waitForTimeout(250);
  await page.screenshot({path:`${output}/${route}-${width}.png`,fullPage:true});
  results.push({route,width,url:page.url(),overflow:await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth)});
 }
}
await page.goto(`${baseURL}/accounts`,{waitUntil:'networkidle'});
const card = await page.getByRole('link',{name:'Ver detalhes'}).first().getAttribute('href');
if(card) { await page.goto(card,{waitUntil:'networkidle'}); for(const width of [1440,390]) {await page.setViewportSize({width,height:width===390?844:900}); await page.screenshot({path:`${output}/invoice-${width}.png`,fullPage:true});} }
await writeFile(`${output}/results.json`,JSON.stringify({results,errors},null,2));
console.log(JSON.stringify({results,errors},null,2));
await browser.close();
