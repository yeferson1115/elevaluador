export function parseId(value: any): number | undefined {
  const id = Number(value);
  return isNaN(id) ? undefined : id;
}
