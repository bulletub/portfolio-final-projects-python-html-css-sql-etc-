export type Json =
  | string
  | number
  | boolean
  | null
  | { [key: string]: Json | undefined }
  | Json[]

export type Database = {
  // Allows to automatically instantiate createClient with right options
  // instead of createClient<Database, { PostgrestVersion: 'XX' }>(URL, KEY)
  __InternalSupabase: {
    PostgrestVersion: "14.15"
  }
  public: {
    Tables: {
      admin_roles: {
        Row: {
          created_at: string
          id: number
          role_name: string
          user_id: string
        }
        Insert: {
          created_at?: string
          id?: never
          role_name: string
          user_id: string
        }
        Update: {
          created_at?: string
          id?: never
          role_name?: string
          user_id?: string
        }
        Relationships: [
          {
            foreignKeyName: "admin_roles_user_id_fkey"
            columns: ["user_id"]
            isOneToOne: false
            referencedRelation: "profiles"
            referencedColumns: ["id"]
          },
        ]
      }
      cart: {
        Row: {
          created_at: string
          id: number
          product_id: number
          quantity: number
          user_id: string
        }
        Insert: {
          created_at?: string
          id?: never
          product_id: number
          quantity: number
          user_id: string
        }
        Update: {
          created_at?: string
          id?: never
          product_id?: number
          quantity?: number
          user_id?: string
        }
        Relationships: [
          {
            foreignKeyName: "cart_product_id_fkey"
            columns: ["product_id"]
            isOneToOne: false
            referencedRelation: "products"
            referencedColumns: ["id"]
          },
          {
            foreignKeyName: "cart_user_id_fkey"
            columns: ["user_id"]
            isOneToOne: false
            referencedRelation: "profiles"
            referencedColumns: ["id"]
          },
        ]
      }
      order_groups: {
        Row: {
          address: string
          created_at: string
          discount_amount: number
          id: number
          payment_code: string | null
          payment_method: string
          paypal_order_id: string | null
          promo_code: string | null
          shipping_code: string | null
          shipping_fee: number
          status: string
          user_id: string
        }
        Insert: {
          address: string
          created_at?: string
          discount_amount?: number
          id?: never
          payment_code?: string | null
          payment_method: string
          paypal_order_id?: string | null
          promo_code?: string | null
          shipping_code?: string | null
          shipping_fee?: number
          status?: string
          user_id: string
        }
        Update: {
          address?: string
          created_at?: string
          discount_amount?: number
          id?: never
          payment_code?: string | null
          payment_method?: string
          paypal_order_id?: string | null
          promo_code?: string | null
          shipping_code?: string | null
          shipping_fee?: number
          status?: string
          user_id?: string
        }
        Relationships: [
          {
            foreignKeyName: "order_groups_user_id_fkey"
            columns: ["user_id"]
            isOneToOne: false
            referencedRelation: "profiles"
            referencedColumns: ["id"]
          },
        ]
      }
      orders: {
        Row: {
          id: number
          order_group_id: number
          price: number
          product_id: number
          quantity: number
        }
        Insert: {
          id?: never
          order_group_id: number
          price: number
          product_id: number
          quantity: number
        }
        Update: {
          id?: never
          order_group_id?: number
          price?: number
          product_id?: number
          quantity?: number
        }
        Relationships: [
          {
            foreignKeyName: "orders_order_group_id_fkey"
            columns: ["order_group_id"]
            isOneToOne: false
            referencedRelation: "order_groups"
            referencedColumns: ["id"]
          },
          {
            foreignKeyName: "orders_product_id_fkey"
            columns: ["product_id"]
            isOneToOne: false
            referencedRelation: "products"
            referencedColumns: ["id"]
          },
        ]
      }
      products: {
        Row: {
          category: string
          created_at: string
          description: string | null
          id: number
          image_path: string | null
          name: string
          price: number
          stock: number
          subcategory: string | null
          updated_at: string
        }
        Insert: {
          category: string
          created_at?: string
          description?: string | null
          id?: never
          image_path?: string | null
          name: string
          price: number
          stock?: number
          subcategory?: string | null
          updated_at?: string
        }
        Update: {
          category?: string
          created_at?: string
          description?: string | null
          id?: never
          image_path?: string | null
          name?: string
          price?: number
          stock?: number
          subcategory?: string | null
          updated_at?: string
        }
        Relationships: []
      }
      profiles: {
        Row: {
          account_type: string
          created_at: string
          email: string | null
          id: string
          name: string | null
          preferred_payment_method: string | null
        }
        Insert: {
          account_type?: string
          created_at?: string
          email?: string | null
          id: string
          name?: string | null
          preferred_payment_method?: string | null
        }
        Update: {
          account_type?: string
          created_at?: string
          email?: string | null
          id?: string
          name?: string | null
          preferred_payment_method?: string | null
        }
        Relationships: []
      }
      user_addresses: {
        Row: {
          address: string
          created_at: string
          full_name: string
          id: number
          is_default: boolean
          user_id: string
        }
        Insert: {
          address: string
          created_at?: string
          full_name: string
          id?: never
          is_default?: boolean
          user_id: string
        }
        Update: {
          address?: string
          created_at?: string
          full_name?: string
          id?: never
          is_default?: boolean
          user_id?: string
        }
        Relationships: [
          {
            foreignKeyName: "user_addresses_user_id_fkey"
            columns: ["user_id"]
            isOneToOne: false
            referencedRelation: "profiles"
            referencedColumns: ["id"]
          },
        ]
      }
    }
    Views: {
      [_ in never]: never
    }
    Functions: {
      create_order: {
        Args: {
          p_address: string
          p_cart_ids: number[]
          p_discount_amount?: number
          p_payment_code?: string
          p_payment_method: string
          p_paypal_order_id?: string
          p_promo_code?: string
          p_shipping_code?: string
          p_shipping_fee?: number
        }
        Returns: number
      }
      is_admin: { Args: never; Returns: boolean }
    }
    Enums: {
      [_ in never]: never
    }
    CompositeTypes: {
      [_ in never]: never
    }
  }
}

type DatabaseWithoutInternals = Omit<Database, "__InternalSupabase">

type DefaultSchema = DatabaseWithoutInternals[Extract<keyof Database, "public">]

export type Tables<
  DefaultSchemaTableNameOrOptions extends
    | keyof (DefaultSchema["Tables"] & DefaultSchema["Views"])
    | { schema: keyof DatabaseWithoutInternals },
  TableName extends DefaultSchemaTableNameOrOptions extends {
    schema: keyof DatabaseWithoutInternals
  }
    ? keyof (DatabaseWithoutInternals[DefaultSchemaTableNameOrOptions["schema"]]["Tables"] &
        DatabaseWithoutInternals[DefaultSchemaTableNameOrOptions["schema"]]["Views"])
    : never = never,
> = DefaultSchemaTableNameOrOptions extends {
  schema: keyof DatabaseWithoutInternals
}
  ? (DatabaseWithoutInternals[DefaultSchemaTableNameOrOptions["schema"]]["Tables"] &
      DatabaseWithoutInternals[DefaultSchemaTableNameOrOptions["schema"]]["Views"])[TableName] extends {
      Row: infer R
    }
    ? R
    : never
  : DefaultSchemaTableNameOrOptions extends keyof (DefaultSchema["Tables"] &
        DefaultSchema["Views"])
    ? (DefaultSchema["Tables"] &
        DefaultSchema["Views"])[DefaultSchemaTableNameOrOptions] extends {
        Row: infer R
      }
      ? R
      : never
    : never

export type TablesInsert<
  DefaultSchemaTableNameOrOptions extends
    | keyof DefaultSchema["Tables"]
    | { schema: keyof DatabaseWithoutInternals },
  TableName extends DefaultSchemaTableNameOrOptions extends {
    schema: keyof DatabaseWithoutInternals
  }
    ? keyof DatabaseWithoutInternals[DefaultSchemaTableNameOrOptions["schema"]]["Tables"]
    : never = never,
> = DefaultSchemaTableNameOrOptions extends {
  schema: keyof DatabaseWithoutInternals
}
  ? DatabaseWithoutInternals[DefaultSchemaTableNameOrOptions["schema"]]["Tables"][TableName] extends {
      Insert: infer I
    }
    ? I
    : never
  : DefaultSchemaTableNameOrOptions extends keyof DefaultSchema["Tables"]
    ? DefaultSchema["Tables"][DefaultSchemaTableNameOrOptions] extends {
        Insert: infer I
      }
      ? I
      : never
    : never

export type TablesUpdate<
  DefaultSchemaTableNameOrOptions extends
    | keyof DefaultSchema["Tables"]
    | { schema: keyof DatabaseWithoutInternals },
  TableName extends DefaultSchemaTableNameOrOptions extends {
    schema: keyof DatabaseWithoutInternals
  }
    ? keyof DatabaseWithoutInternals[DefaultSchemaTableNameOrOptions["schema"]]["Tables"]
    : never = never,
> = DefaultSchemaTableNameOrOptions extends {
  schema: keyof DatabaseWithoutInternals
}
  ? DatabaseWithoutInternals[DefaultSchemaTableNameOrOptions["schema"]]["Tables"][TableName] extends {
      Update: infer U
    }
    ? U
    : never
  : DefaultSchemaTableNameOrOptions extends keyof DefaultSchema["Tables"]
    ? DefaultSchema["Tables"][DefaultSchemaTableNameOrOptions] extends {
        Update: infer U
      }
      ? U
      : never
    : never

export const Constants = {
  public: {
    Enums: {},
  },
} as const
