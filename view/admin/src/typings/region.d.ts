declare namespace Region {
  interface RegionItem {
    region_id: number;
    path: string;
    parent_id: number;
    type: 'province' | 'city' | 'district' | 'street' | 'town';
    name: string;
    level: number;
    code: string;
    snum: number;
    updated_at: string;
    deleted_at: string | null;
    created_at: string;
    children?: RegionItem[];
    parentRegion?: RegionItem;
  }

  interface RegionForm {
    region_id?: number;
    parent_id: number;
    type: 'province' | 'city' | 'district' | 'street' | 'town';
    name: string;
    code: string;
    snum: number;
  }

  interface MergeParams {
    target_region_id: number;
    source_region_ids: number[];
  }

  interface SplitParams {
    parent_region_id: number;
    new_regions: Omit<RegionForm, 'region_id'>[];
  }
}
export namespace Region {}
